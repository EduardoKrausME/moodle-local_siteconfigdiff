<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/domain/applier.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/** Fail-closed selective application; never writes directly to configuration tables. */
final class applier {
    /** Require both explicit site admin allowlisting and a capability. */
    public static function require_authorized(): void {
        global $USER;
        require_capability('local/siteconfigdiff:apply', \context_system::instance());
        $allowed = array_filter(array_map('trim', explode(',', (string)get_config('local_siteconfigdiff', 'allowedappliers'))));
        if (!is_siteadmin($USER) || !in_array((string)$USER->id, $allowed, true)) {
            throw new \required_capability_exception(\context_system::instance(),
                'local/siteconfigdiff:apply', 'nopermissions', '');
        }
    }

    /**
     * @param array $plan Original previewed and server-side persisted plan.
     * @param callable $audit A callback recording each result durably.
     * @return array Summary with partial results; no claim of transaction rollback.
     */
    public function apply(array $plan, callable $audit): array {
        self::require_authorized();
        if (($plan['schema'] ?? null) !== 1 || !in_array(($plan['kind'] ?? ''), ['apply', 'reversal'], true)) {
            throw new \InvalidArgumentException('Invalid plan.');
        }
        global $CFG;
        if (($plan['installation'] ?? '') !== hash('sha256', (string)$CFG->wwwroot)) {
            throw new \InvalidArgumentException('The destination installation changed.');
        }
        $summary = ['applied' => 0, 'stale' => 0, 'rejected' => 0, 'failed' => 0];
        foreach ($plan['entries'] as $entry) {
            $component = $entry['component'] ?? '';
            $key = $entry['name'] ?? '';
            $expected = $entry['expected'] ?? [];
            $desired = $entry['desired'] ?? [];
            $outcome = 'rejected';
            $reason = 'not_allowlisted';
            if (policy::writable($component, $key) &&
                ($expected['kind'] ?? '') === 'value' && ($desired['kind'] ?? '') === 'value' &&
                ($expected['type'] ?? '') === 'string' && ($desired['type'] ?? '') === 'string' &&
                policy::validate_value($component, $key, $desired['value'])) {
                // The get_config return value is false for absent values; absence is rejected.
                $current = get_config($component === 'core' ? null : $component, $key);
                if ($current === false || !normalizer::equal(policy::entry($component, $key, $current), $expected)) {
                    $outcome = 'stale';
                    $reason = 'destination_changed_since_preview';
                } else {
                    try {
                        if (set_config($key, $desired['value'], $component === 'core' ? null : $component)) {
                            $outcome = 'applied';
                            $reason = 'ok';
                        } else {
                            $outcome = 'failed';
                            $reason = 'set_config_failed';
                        }
                    } catch (\Throwable $e) {
                        $outcome = 'failed';
                        $reason = 'set_config_exception';
                    }
                }
            }
            // A failing audit stops the batch; deliberately do not hide that failure.
            $audit($component, $key, $outcome, $reason,
                ($expected['kind'] ?? '') === 'value' ? $expected : [],
                ($desired['kind'] ?? '') === 'value' ? $desired : []);
            $summary[$outcome]++;
        }
        return $summary;
    }
}
