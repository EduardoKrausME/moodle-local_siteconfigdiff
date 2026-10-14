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
 * tests/behat/behat_local_siteconfigdiff.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Prepare an allowed configuration change without any production dependency. */
class behat_local_siteconfigdiff extends behat_base {
    /**
     * @Given /^I open an explicitly authorized configuration preview fixture$/
     */
    public function i_open_an_explicitly_authorized_configuration_preview_fixture(): void {
        global $CFG, $DB;
        $admin = $DB->get_record('user', ['username' => 'admin'], '*', MUST_EXIST);
        set_config('allowedappliers', (string)$admin->id, 'local_siteconfigdiff');
        set_config('lang', 'en');
        $target = (new \local_siteconfigdiff\domain\collector())->collect();
        $source = $target;
        $source['components']['core']['lang'] = \local_siteconfigdiff\domain\policy::entry('core', 'lang', 'pt_br');
        $source = \local_siteconfigdiff\domain\snapshot_codec::seal($source);
        $repo = new \local_siteconfigdiff\repository\history();
        $id = $repo->store_snapshot('Behat approval source', $source);
        $diff = (new \local_siteconfigdiff\domain\comparator())->compare($source, $target, true);
        $compareid = $repo->store_comparison($id, 0, $diff);
        $this->getSession()->visit($CFG->wwwroot . '/local/siteconfigdiff/index.php?view=compare&id=' . $compareid);
    }
}
