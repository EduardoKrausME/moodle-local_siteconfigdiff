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
 * classes/event/activity_logged.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\event;

/**
 * A generic traceable event for configuration snapshot/comparison/plan operations.
 */
final class activity_logged extends \core\event\base {
    /**
     * Initialize the configuration audit event.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return the localized event name.
     * @return string Result.
     */
    public static function get_name(): string {
        return get_string('eventlogged', 'local_siteconfigdiff');
    }

    /**
     * Describe the audit event.
     * @return string Result.
     */
    public function get_description(): string {
        return get_string('eventdescription', 'local_siteconfigdiff', ['action' => $this->other['action']]);
    }

    /**
     * Return the event's dashboard link.
     * @return \moodle_url Result.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/siteconfigdiff/index.php');
    }
}
