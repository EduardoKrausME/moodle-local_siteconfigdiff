<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * classes/domain/collector.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/**
 * Reads Moodle config exclusively through the supported configuration API.
 */
final class collector {
    /**
     * Collect sanitized core and plugin configuration values.
     * @return array Result.
     */
    public function collect(): array {
        global $CFG;
        $components = ['core' => []];
        $global = get_config('core');
        foreach ($global as $key => $value) {
            if (preg_match('/^[a-z][a-z0-9_]*$/D', $key)) {
                $components['core'][$key] = policy::entry('core', $key, $value);
            }
        }
        $plugins = [];
        foreach (\core_plugin_manager::instance()->get_plugins() as $type => $infos) {
            foreach ($infos as $info) {
                $component = $info->component;
                if (!preg_match('/^[a-z][a-z0-9_]*$/D', $component)) {
                    continue;
                }
                $plugins[$component] = [
                    'version' => (string)($info->versiondisk ?? ''),
                    'release' => (string)($info->release ?? ''),
                ];
                $config = get_config($component);
                $components[$component] = [];
                if (!empty($config)) {
                    foreach ($config as $key => $value) {
                        if (preg_match('/^[a-z][a-z0-9_]*$/D', $key)) {
                            $components[$component][$key] = policy::entry($component, $key, $value);
                        }
                    }
                }
            }
        }
        $snapshot = [
            'schema' => 1,
            // A site fingerprint is for matching environments, not secret authentication.
            'installation' => hash('sha256', (string)$CFG->wwwroot),
            'moodle' => (string)($CFG->release ?? $CFG->version ?? 'unknown'),
            'generated_at' => time(),
            'plugins' => $plugins,
            'components' => $components,
        ];
        return snapshot_codec::seal($snapshot);
    }
}
