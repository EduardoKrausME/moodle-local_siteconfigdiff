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
 * Upgrade steps for Site configuration diff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade database tables from the 1.0.0 shortened prefix to the full component prefix.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_local_siteconfigdiff_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026101401) {
        $dbman = $DB->get_manager();
        $renames = [
            'local_scd_snapshot' => 'local_siteconfigdiff_snap',
            'local_scd_compare' => 'local_siteconfigdiff_diff',
            'local_scd_plan' => 'local_siteconfigdiff_plan',
            'local_scd_log' => 'local_siteconfigdiff_log',
        ];

        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            $newtable = new xmldb_table($newname);
            $oldexists = $dbman->table_exists($oldtable);
            $newexists = $dbman->table_exists($newtable);

            // Reject ambiguous/partial migrations rather than overwrite existing data.
            if ($oldexists && $newexists) {
                throw new coding_exception('Both legacy and current tables exist: ' . $oldname);
            }
            if (!$oldexists && !$newexists) {
                throw new coding_exception('Neither legacy nor current table exists: ' . $oldname);
            }
            if ($oldexists) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        upgrade_plugin_savepoint(true, 2026101401, 'local', 'siteconfigdiff');
    }

    return true;
}
