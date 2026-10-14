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
 * lang/en/local_siteconfigdiff.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'Actions';
$string['added'] = 'Added';
$string['all'] = 'All';
$string['allowedappliers'] = 'Authorized site administrator IDs';
$string['allowedappliers_help'] = 'Comma-separated user IDs. Empty disables all automatic applications even for site administrators.';
$string['apply_plan'] = 'Apply the reviewed changes';
$string['audit'] = 'Audit results';
$string['author'] = 'Actor ID';
$string['back'] = 'Back to dashboard';
$string['changed'] = 'Changed';
$string['compare'] = 'Compare snapshots';
$string['comparison'] = 'Comparison';
$string['comparisonnotice'] = 'Differences are read-only until an authorized administrator builds and reviews a selective plan. Redacted settings cannot be compared by value.';
$string['comparisons'] = 'Comparison history';
$string['component'] = 'Component';
$string['create_plan'] = 'Create application preview';
$string['create_snapshot'] = 'Capture current site snapshot';
$string['created'] = 'Created';
$string['currentsite'] = 'Current site (live)';
$string['dashboard'] = 'Configuration comparisons';
$string['description'] = 'Compare sanitized snapshots from Moodle environments. Only expressly reviewed settings may be applied after independent approval.';
$string['destination'] = 'Destination';
$string['download_csv'] = 'Download CSV';
$string['download_json'] = 'Download JSON';
$string['download_plan'] = 'Export application plan (JSON)';
$string['eventdescription'] = 'Configuration comparison operation: {$a->action}';
$string['eventlogged'] = 'Configuration comparison activity';
$string['filter'] = 'Filter';
$string['group'] = 'Administrative group';
$string['hidden'] = 'Value hidden';
$string['impact'] = 'Potential impact';
$string['impact_calendar'] = 'Calendar behavior';
$string['impact_language'] = 'Site language';
$string['impact_locale'] = 'Regional settings';
$string['impact_theme'] = 'Theme, cache and presentation';
$string['impact_time'] = 'Time zone and scheduling';
$string['impact_unknown'] = 'Unknown';
$string['impact_unreviewed'] = 'Unreviewed or sensitive setting';
$string['import_snapshot'] = 'Import sanitized snapshot';
$string['installation'] = 'Installation fingerprint';
$string['jsonfile'] = 'Sanitized JSON file (max 1 MiB)';
$string['locktimeout'] = 'Another application is running for this plan.';
$string['low'] = 'Low';
$string['manual'] = 'Manual';
$string['missingplugin'] = 'Missing plugin at destination';
$string['newvalue'] = 'Source value (proposed)';
$string['oldvalue'] = 'Destination value (current at preview)';
$string['operationfailed'] = 'Operation rejected. Check the input, permissions and developer diagnostic log.';
$string['plan'] = 'Application plan';
$string['plans'] = 'Application plans';
$string['planwarning'] = 'Review each entry. Application checks the expected destination value immediately before writing. External side effects are not rolled back.';
$string['pluginchanges'] = 'Plugin manifest differences';
$string['pluginname'] = 'Site configuration diff';
$string['pluginversions'] = 'Plugin versions (source / destination)';
$string['prepare_reversal'] = 'Prepare reversal preview';
$string['privacy:metadata:local_siteconfigdiff_diff'] = 'Creator of a comparison';
$string['privacy:metadata:local_siteconfigdiff_log'] = 'Actor performing a setting change';
$string['privacy:metadata:local_siteconfigdiff_plan'] = 'Creator of an application plan';
$string['privacy:metadata:local_siteconfigdiff_snap'] = 'Creator of a snapshot';
$string['reason'] = 'Reason';
$string['reason_destination_changed_since_preview'] = 'Destination changed since preview';
$string['reason_not_allowlisted'] = 'Setting not allowed';
$string['reason_ok'] = 'Applied by set_config()';
$string['reason_set_config_exception'] = 'Moodle raised an error';
$string['reason_set_config_failed'] = 'Moodle rejected the change';
$string['removed'] = 'Removed';
$string['risk'] = 'Risk';
$string['risk_low'] = 'Low';
$string['risk_manual'] = 'Manual intervention';
$string['risk_unknown'] = 'Unreviewed';
$string['saved'] = 'Operation saved.';
$string['search'] = 'Search component or setting';
$string['select'] = 'Select';
$string['setting'] = 'Setting';
$string['siteconfigdiff:apply'] = 'Apply allowlisted settings after explicit authorization';
$string['siteconfigdiff:export'] = 'Export configuration snapshots and comparisons';
$string['siteconfigdiff:import'] = 'Import sanitized configuration snapshots';
$string['siteconfigdiff:view'] = 'View sanitized comparisons';
$string['snapshotname'] = 'Snapshot name';
$string['snapshots'] = 'Saved snapshots';
$string['source'] = 'Source';
$string['sourceversion'] = 'Source version';
$string['state_added'] = 'Added';
$string['state_applied'] = 'Applied';
$string['state_changed'] = 'Changed';
$string['state_completed'] = 'Completed';
$string['state_failed'] = 'Failed';
$string['state_hidden'] = 'Redacted or uncertain';
$string['state_missing_destination'] = 'Not installed at destination';
$string['state_missing_source'] = 'Not installed at source';
$string['state_partial'] = 'Partially applied';
$string['state_pending'] = 'Awaiting approval';
$string['state_rejected'] = 'Rejected';
$string['state_removed'] = 'Removed';
$string['state_running'] = 'Running';
$string['state_stale'] = 'Stale';
$string['state_version_mismatch'] = 'Plugin version differs';
$string['status'] = 'Status';
$string['targetversion'] = 'Destination version';
$string['unknown'] = 'Unknown';
