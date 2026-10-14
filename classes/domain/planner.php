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
 * classes/domain/planner.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/** Generates immutable, optimistic-concurrency-aware preview plans. */
final class planner {
    public function create(array $comparison, array $rowids): array {
        if (($comparison['target'] ?? '') !== 'current') {
            throw new \InvalidArgumentException('Only comparisons against the current site can be applied.');
        }
        $selected = [];
        foreach ($rowids as $id) {
            if (!is_int($id) || !isset($comparison['rows'][$id]) ||
                $comparison['rows'][$id]['id'] !== $id) {
                throw new \InvalidArgumentException('Invalid comparison row identifier.');
            }
            $row = $comparison['rows'][$id];
            if (empty($row['applicable']) || !policy::writable($row['component'], $row['name']) ||
                $row['target']['kind'] !== 'value' || $row['source']['kind'] !== 'value' ||
                !policy::validate_value($row['component'], $row['name'], $row['source']['value'])) {
                throw new \InvalidArgumentException('Setting is not approved for automatic application.');
            }
            $selected[$row['component'] . ':' . $row['name']] = [
                'component' => $row['component'], 'name' => $row['name'],
                'expected' => $row['target'], 'desired' => $row['source'],
                'risk' => $row['risk'], 'impact' => $row['impact'],
            ];
        }
        if (!$selected || count($selected) > 100) {
            throw new \InvalidArgumentException('Select between 1 and 100 eligible settings.');
        }
        ksort($selected, SORT_STRING);
        return ['schema' => 1, 'kind' => 'apply', 'installation' => $comparison['target_installation'], 'entries' => array_values($selected)];
    }

    /** Generates a new preview plan only; external side effects are not rolled back. */
    public function reverse(array $appliedentries, string $installation): array {
        $entries = [];
        foreach ($appliedentries as $entry) {
            if (!policy::writable($entry['component'], $entry['name']) ||
                $entry['expected']['kind'] !== 'value' || $entry['desired']['kind'] !== 'value' ||
                !policy::validate_value($entry['component'], $entry['name'], $entry['expected']['value'])) {
                continue;
            }
            $entries[] = [
                'component' => $entry['component'], 'name' => $entry['name'],
                'expected' => $entry['desired'], 'desired' => $entry['expected'],
                'risk' => 'low', 'impact' => policy::impact($entry['component'], $entry['name']),
            ];
        }
        if (!$entries) {
            throw new \InvalidArgumentException('Nothing eligible for a reversal preview.');
        }
        return ['schema' => 1, 'kind' => 'reversal', 'installation' => $installation, 'entries' => $entries];
    }
}
