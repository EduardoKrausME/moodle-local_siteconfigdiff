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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/domain/comparator.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/**
 * Compares source to destination without materializing sensitive values.
 */
final class comparator {
    /**
     * Compare sanitized configuration snapshots.
     * @param array $source Argument.
     * @param array $target Argument.
     * @param bool $islive Argument.
     * @return array Result.
     */
    public function compare(array $source, array $target, bool $islive): array {
        $components = array_unique(array_merge(array_keys($source['components']), array_keys($target['components'])));
        sort($components, SORT_STRING);
        $rows = [];
        foreach ($components as $component) {
            $from = $source['components'][$component] ?? [];
            $to = $target['components'][$component] ?? [];
            $names = array_unique(array_merge(array_keys($from), array_keys($to)));
            sort($names, SORT_STRING);
            foreach ($names as $name) {
                $hasfrom = array_key_exists($name, $from);
                $hasto = array_key_exists($name, $to);
                $before = $hasto ? $to[$name] : null;
                $after = $hasfrom ? $from[$name] : null;
                if (!$hasfrom) {
                    $status = 'removed';
                } else if (!$hasto) {
                    $status = 'added';
                } else if ($from[$name]['kind'] === 'redacted' || $to[$name]['kind'] === 'redacted') {
                    // A secret's value cannot be compared without exposing it or a guessable hash.
                    $status = 'hidden';
                } else if (normalizer::equal($before, $after)) {
                    continue;
                } else {
                    $status = 'changed';
                }
                $pluginmissing = $component !== 'core' && !isset($target['plugins'][$component]);
                $risk = policy::risk($component, $name);
                $group = $component === 'core' ? 'core' : explode('_', $component, 2)[0];
                $applicable = $islive && !$pluginmissing && $hasfrom && $hasto &&
                    $before['kind'] === 'value' && $after['kind'] === 'value' &&
                    $before['type'] === 'string' && $after['type'] === 'string' &&
                    policy::writable($component, $name) &&
                    policy::validate_value($component, $name, $after['value']);
                $rows[] = [
                    'id' => count($rows), 'component' => $component, 'name' => $name,
                    'status' => $status, 'risk' => $risk, 'group' => $group,
                    'impact' => policy::impact($component, $name),
                    'source' => $after, 'target' => $before,
                    'applicable' => $applicable, 'pluginmissing' => $pluginmissing,
                    'source_version' => $source['plugins'][$component]['version'] ?? $source['moodle'],
                    'target_version' => $target['plugins'][$component]['version'] ??
                        ($component === 'core' ? $target['moodle'] : ''),
                ];
            }
        }
        $pluginchanges = [];
        $allplugins = array_unique(array_merge(array_keys($source['plugins']), array_keys($target['plugins'])));
        sort($allplugins, SORT_STRING);
        foreach ($allplugins as $plugin) {
            $src = $source['plugins'][$plugin] ?? null;
            $dst = $target['plugins'][$plugin] ?? null;
            if (!$src || !$dst || !normalizer::equal($src, $dst)) {
                $pluginchanges[] = [
                    'component' => $plugin,
                    'status' => $dst === null ? 'missing_destination' : ($src === null ? 'missing_source' : 'version_mismatch'),
                    'source_version' => $src['version'] ?? '',
                    'target_version' => $dst['version'] ?? '',
                ];
            }
        }
        return [
            'schema' => 1, 'target' => $islive ? 'current' : 'snapshot',
            'source_installation' => $source['installation'],
            'target_installation' => $target['installation'],
            'source_moodle' => $source['moodle'], 'target_moodle' => $target['moodle'],
            'source_plugins' => $source['plugins'], 'target_plugins' => $target['plugins'],
            'rows' => $rows, 'pluginchanges' => $pluginchanges,
        ];
    }
}
