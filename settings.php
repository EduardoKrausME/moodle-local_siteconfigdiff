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
 * Plugin settings.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_siteconfigdiff', get_string('pluginname', 'local_siteconfigdiff'));
    $settings->add(new admin_setting_configtext(
        'local_siteconfigdiff/allowedappliers',
        get_string('allowedappliers', 'local_siteconfigdiff'),
        get_string('allowedappliers_help', 'local_siteconfigdiff'),
        '', PARAM_TEXT
    ));
    $ADMIN->add('localplugins', $settings);
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_siteconfigdiff_dashboard', get_string('dashboard', 'local_siteconfigdiff'),
        new moodle_url('/local/siteconfigdiff/index.php'),
        'local/siteconfigdiff:view'
    ));
}
