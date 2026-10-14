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
 * classes/privacy/provider.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/** Personal identifiers are in audit ownership columns only, not in snapshots. */
final class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {
    private const TABLES = ['local_siteconfigdiff_snap', 'local_siteconfigdiff_diff', 'local_siteconfigdiff_plan', 'local_siteconfigdiff_log'];

    public static function get_metadata(collection $collection): collection {
        foreach (self::TABLES as $table) {
            $collection->add_database_table($table, ['userid' => 'privacy:metadata:' . $table],
                'privacy:metadata:' . $table);
        }
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contexts = new contextlist();
        foreach (self::TABLES as $table) {
            if ($DB->record_exists($table, ['userid' => $userid])) {
                $contexts->add_context(\context_system::instance()->id);
                break;
            }
        }
        return $contexts;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $user = $contextlist->get_user();
        if (!in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            return;
        }
        $data = new \stdClass();
        foreach (self::TABLES as $table) {
            // Avoid exporting other users' snapshots, logs, or personally attributed data.
            $records = $DB->get_records($table, ['userid' => $user->id]);
            $data->{$table} = array_values(array_map(static function($record) use ($table) {
                $result = (array)$record;
                if (array_key_exists('document', $result)) {
                    // Audit owner exports may contain sanitized config values by design.
                    $result['document'] = json_decode($result['document'], true);
                }
                return $result;
            }, $records));
        }
        writer::with_context(\context_system::instance())->export_data(
            [get_string('pluginname', 'local_siteconfigdiff')], $data);
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        // Preserve change history as a non-personal record; detach all actor user IDs.
        foreach (self::TABLES as $table) {
            $DB->set_field_select($table, 'userid', 0, 'userid <> ?', [0]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (!in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            return;
        }
        foreach (self::TABLES as $table) {
            $DB->set_field($table, 'userid', 0, ['userid' => $contextlist->get_user()->id]);
        }
    }
}
