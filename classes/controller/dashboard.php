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
 * classes/controller/dashboard.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\controller;

use local_siteconfigdiff\domain\applier;
use local_siteconfigdiff\domain\collector;
use local_siteconfigdiff\domain\comparator;
use local_siteconfigdiff\domain\normalizer;
use local_siteconfigdiff\domain\planner;
use local_siteconfigdiff\domain\snapshot_codec;
use local_siteconfigdiff\repository\history;

/**
 * MVC controller: PHP handles transitions, Mustache only displays plain arrays.
 */
final class dashboard {
    /** @var history repo property. */
    private history $repo;
    /** @var \context_system context property. */
    private \context_system $context;

    /**
     * Render the restricted configuration dashboard.
     */
    public function run(): void {
        global $PAGE, $OUTPUT;
        require_login();
        $this->context = \context_system::instance();
        require_capability('local/siteconfigdiff:view', $this->context);
        $this->repo = new history();
        $PAGE->set_context($this->context);
        $PAGE->set_url(new \moodle_url('/local/siteconfigdiff/index.php'));
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title(get_string('dashboard', 'local_siteconfigdiff'));
        $PAGE->set_heading(get_string('dashboard', 'local_siteconfigdiff'));

        $export = optional_param('export', '', PARAM_ALPHA);
        $id = optional_param('id', 0, PARAM_INT);
        if ($export !== '') {
            $this->download($export, $id);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_sesskey();
            $action = required_param('action', PARAM_ALPHA);
            try {
                $target = $this->dispatch($action);
                redirect(new \moodle_url('/local/siteconfigdiff/index.php', $target),
                    get_string('saved', 'local_siteconfigdiff'), null, \core\output\notification::NOTIFY_SUCCESS);
            } catch (\Throwable $e) {
                // Do not reveal uploaded content, configuration values, or server internals.
                \core\notification::error(get_string('operationfailed', 'local_siteconfigdiff'));
                debugging(get_class($e), DEBUG_DEVELOPER);
            }
        }
        echo $OUTPUT->header();
        if (optional_param('view', '', PARAM_ALPHA) === 'compare' && $id > 0) {
            echo $OUTPUT->render_from_template('local_siteconfigdiff/comparison', $this->comparison_context($id));
        } else if (optional_param('view', '', PARAM_ALPHA) === 'plan' && $id > 0) {
            echo $OUTPUT->render_from_template('local_siteconfigdiff/plan', $this->plan_context($id));
        } else {
            echo $OUTPUT->render_from_template('local_siteconfigdiff/dashboard', $this->dashboard_context());
        }
        echo $OUTPUT->footer();
    }

    /**
     * Document the following operation.
     *
     * @return array<string, int|string>
     */
    private function dispatch(string $action): array {
        global $DB;
        switch ($action) {
            case 'snapshot':
                require_capability('local/siteconfigdiff:export', $this->context);
                $doc = (new collector())->collect();
                $name = trim(required_param('name', PARAM_TEXT));
                if ($name === '' || strlen($name) > 255) {
                    throw new \InvalidArgumentException('Invalid snapshot name.');
                }
                $id = $this->repo->store_snapshot($name, $doc);
                $this->emit('snapshot_created', $id);
                return [];
            case 'import':
                require_capability('local/siteconfigdiff:import', $this->context);
                $bytes = $this->read_upload();
                $doc = snapshot_codec::decode($bytes);
                $name = trim(required_param('name', PARAM_TEXT));
                if ($name === '' || strlen($name) > 255) {
                    throw new \InvalidArgumentException('Invalid snapshot name.');
                }
                $id = $this->repo->store_snapshot($name, $doc);
                $this->emit('snapshot_imported', $id);
                return [];
            case 'compare':
                $leftid = required_param('leftid', PARAM_INT);
                $rightid = required_param('rightid', PARAM_INT);
                if ($leftid <= 0 || $rightid < 0) {
                    throw new \InvalidArgumentException('Invalid source/destination.');
                }
                $left = $this->repo->snapshot($leftid);
                $right = $rightid ? $this->repo->snapshot($rightid) : (new collector())->collect();
                $doc = (new comparator())->compare($left, $right, !$rightid);
                $id = $this->repo->store_comparison($leftid, $rightid, $doc);
                $this->emit('comparison_created', $id);
                return ['view' => 'compare', 'id' => $id];
            case 'plan':
                applier::require_authorized();
                $comparisonid = required_param('comparisonid', PARAM_INT);
                $comparison = $this->repo->comparison($comparisonid);
                $values = optional_param_array('rows', [], PARAM_INT);
                $plan = (new planner())->create($comparison, array_values($values));
                $id = $this->repo->store_plan($comparisonid, $plan);
                $this->emit('plan_created', $id);
                return ['view' => 'plan', 'id' => $id];
            case 'reverse':
                applier::require_authorized();
                $previousid = required_param('planid', PARAM_INT);
                $previous = $this->repo->plan($previousid);
                if (!in_array($previous->status, ['completed', 'partial'], true)) {
                    throw new \InvalidArgumentException('Only plans with applied changes can be reversed.');
                }
                $oldplan = json_decode($previous->document, true, 32, JSON_THROW_ON_ERROR);
                $successful = [];
                foreach ($this->repo->logs($previousid) as $log) {
                    if ($log->outcome === 'applied') {
                        foreach ($oldplan['entries'] as $entry) {
                            if ($log->component === $entry['component'] && $log->setting === $entry['name']) {
                                $successful[] = $entry;
                                break;
                            }
                        }
                    }
                }
                $plan = (new planner())->reverse($successful, hash('sha256', (string)$GLOBALS['CFG']->wwwroot));
                $id = $this->repo->store_plan((int)$previous->comparisonid, $plan);
                $this->emit('plan_created', $id);
                return ['view' => 'plan', 'id' => $id];
            case 'apply':
                applier::require_authorized();
                $planid = required_param('planid', PARAM_INT);
                $lockfactory = \core\lock\lock_config::get_lock_factory('local_siteconfigdiff');
                $lock = $lockfactory->get_lock('apply-config', 10, 60);
                if (!$lock) {
                    throw new \moodle_exception('locktimeout', 'local_siteconfigdiff');
                }
                try {
                    $record = $this->repo->plan($planid);
                    if ($record->status !== 'pending') {
                        throw new \InvalidArgumentException('Plan is no longer pending.');
                    }
                    $plan = json_decode($record->document, true, 32, JSON_THROW_ON_ERROR);
                    $DB->set_field('local_siteconfigdiff_plan', 'status', 'running', ['id' => $planid]);
                    $summary = (new applier())->apply($plan,
                        function($component, $key, $outcome, $reason, $before, $after) use ($planid) {
                            $this->repo->log($planid, $component, $key, $outcome, $reason, $before, $after);
                        });
                    $allapplied = $summary['applied'] === count($plan['entries']);
                    $status = $allapplied ? 'completed' : ($summary['applied'] > 0 ? 'partial' : 'failed');
                    $DB->set_field('local_siteconfigdiff_plan', 'status', $status, ['id' => $planid]);
                    if ($summary['applied'] > 0) {
                        $DB->set_field('local_siteconfigdiff_plan', 'timeapplied', time(), ['id' => $planid]);
                    }
                    $this->emit('plan_applied', $planid);
                    return ['view' => 'plan', 'id' => $planid];
                } catch (\Throwable $e) {
                    if (isset($record) && $record->status === 'pending') {
                        $DB->set_field('local_siteconfigdiff_plan', 'status', 'failed', ['id' => $planid]);
                    }
                    throw $e;
                } finally {
                    $lock->release();
                }
            default:
                throw new \InvalidArgumentException('Unsupported action.');
        }
    }

    /**
     * Read a sanitized snapshot upload.
     * @return string Result.
     */
    private function read_upload(): string {
        if (!isset($_FILES['snapshot']) || !is_array($_FILES['snapshot'])) {
            throw new \InvalidArgumentException('Missing upload.');
        }
        $file = $_FILES['snapshot'];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_int($file['size']) ||
            $file['size'] > snapshot_codec::MAX_BYTES || $file['size'] <= 0 ||
            !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid upload.');
        }
        if (!is_string($file['name']) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') {
            throw new \InvalidArgumentException('Not JSON file.');
        }
        $bytes = file_get_contents($file['tmp_name'], false, null, 0, snapshot_codec::MAX_BYTES + 1);
        if ($bytes === false) {
            throw new \InvalidArgumentException('Unable to read upload.');
        }
        return $bytes;
    }

    /**
     * Export the requested sanitized document.
     * @param string $format Argument.
     * @param int $id Argument.
     */
    private function download(string $format, int $id): void {
        global $USER;
        require_capability('local/siteconfigdiff:export', $this->context);
        if ($id < 1) {
            throw new \invalid_parameter_exception('id');
        }
        if ($format === 'json') {
            $body = snapshot_codec::encode($this->repo->snapshot($id));
            $mime = 'application/json';
        } else if ($format === 'comparisonjson' || $format === 'csv' || $format === 'planjson') {
            if ($format === 'planjson') {
                applier::require_authorized();
                $record = $this->repo->plan($id);
                $body = json_encode(json_decode($record->document, true, 32, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT);
            } else {
                $document = $this->repo->comparison($id);
                if ($format === 'comparisonjson') {
                    $body = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                } else {
                    $temp = fopen('php://temp', 'w+');
                    fputcsv($temp, ['component', 'name', 'state', 'risk', 'impact', 'source', 'destination', 'applicable']);
                    foreach ($document['rows'] as $row) {
                        $values = [$row['component'], $row['name'], $row['status'], $row['risk'], $row['impact'],
                            normalizer::display($row['source']), normalizer::display($row['target']),
                            empty($row['applicable']) ? 'no' : 'yes'];
                        // Neutralize spreadsheet formula injection in *every* user-controlled CSV cell.
                        fputcsv($temp, array_map(static function($value) {
                            $str = (string)$value;
                            return preg_match('/^[\s\x00-\x1F]*[=+@\-]/', $str) ? "'" . $str : $str;
                        }, $values));
                    }
                    rewind($temp);
                    $body = stream_get_contents($temp);
                    fclose($temp);
                }
            }
            $mime = $format === 'csv' ? 'text/csv' : 'application/json';
        } else {
            throw new \invalid_parameter_exception('export');
        }
        header('Content-Type: ' . $mime . '; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, private');
        header('Content-Disposition: attachment; filename="siteconfigdiff-' .
            $id . '.' . ($format === 'csv' ? 'csv' : 'json') . '"');
        echo $body;
        exit;
    }

    /**
     * Build shared template values.
     * @return array Result.
     */
    private function base_context(): array {
        global $USER;
        $allow = array_filter(array_map('trim', explode(',', (string)get_config('local_siteconfigdiff', 'allowedappliers'))));
        return [
            'actionurl' => (new \moodle_url('/local/siteconfigdiff/index.php'))->out(false),
            'sesskey' => sesskey(),
            'canexport' => has_capability('local/siteconfigdiff:export', $this->context),
            'canimport' => has_capability('local/siteconfigdiff:import', $this->context),
            'canapply' => has_capability('local/siteconfigdiff:apply', $this->context) &&
                is_siteadmin($USER) && in_array((string)$USER->id, $allow, true),
        ];
    }

    /**
     * Build the dashboard view model.
     * @return array Result.
     */
    private function dashboard_context(): array {
        $context = $this->base_context();
        $snapshots = $this->repo->snapshots();
        $context['snapshots'] = array_map(static function($record) {
            return ['id' => (int)$record->id, 'name' => format_string($record->name),
                'origin' => $record->origin, 'created' => userdate($record->timecreated), 'author' => (int)$record->userid];
        }, $snapshots);
        $context['comparisons'] = array_map(static function($record) {
            return ['id' => (int)$record->id, 'leftid' => (int)$record->leftid,
                'rightid' => (int)$record->rightid, 'created' => userdate($record->timecreated), 'author' => (int)$record->userid];
        }, $this->repo->comparisons());
        $context['plans'] = array_map(static function($record) {
            return ['id' => (int)$record->id, 'status' => get_string('state_' . $record->status, 'local_siteconfigdiff'),
                'created' => userdate($record->timecreated), 'author' => (int)$record->userid];
        }, $this->repo->plans());
        return $context;
    }

    /**
     * Build the comparison view model.
     * @param int $id Argument.
     * @return array Result.
     */
    private function comparison_context(int $id): array {
        $ctx = $this->base_context();
        $doc = $this->repo->comparison($id);
        $q = trim(optional_param('q', '', PARAM_TEXT));
        $status = optional_param('status', '', PARAM_ALPHANUMEXT);
        $risk = optional_param('risk', '', PARAM_ALPHA);
        $component = optional_param('component', '', PARAM_COMPONENT);
        $group = optional_param('group', '', PARAM_ALPHANUMEXT);
        $rows = [];
        foreach ($doc['rows'] as $row) {
            if (($status !== '' && $row['status'] !== $status) ||
                ($risk !== '' && $row['risk'] !== $risk) ||
                ($component !== '' && $row['component'] !== $component) ||
                ($group !== '' && $row['group'] !== $group) ||
                ($q !== '' && stripos($row['component'] . ':' . $row['name'], $q) === false)) {
                continue;
            }
            $rows[] = [
                'id' => (int)$row['id'], 'component' => $row['component'], 'name' => $row['name'],
                'status' => get_string('state_' . $row['status'], 'local_siteconfigdiff'),
                'risk' => get_string('risk_' . $row['risk'], 'local_siteconfigdiff'), 'group' => $row['group'],
                'source_version' => $row['source_version'], 'target_version' => $row['target_version'],
                'impact' => get_string(
                    'impact_' . $row['impact'], 'local_siteconfigdiff'),
                'oldvalue' => normalizer::display($row['target']),
                'newvalue' => normalizer::display($row['source']),
                'applicable' => !empty($row['applicable']) && $ctx['canapply'],
                'missingplugin' => !empty($row['pluginmissing']),
            ];
        }
        $ctx['rows'] = $rows;
        $ctx['pluginchanges'] = array_map(static function($item) {
            $item['status'] = get_string('state_' . $item['status'], 'local_siteconfigdiff');
            return $item;
        }, $doc['pluginchanges'] ?? []);
        $ctx['haspluginchanges'] = !empty($ctx['pluginchanges']);
        $ctx['id'] = $id;
        $ctx['q'] = $q;
        $ctx['status'] = $status;
        $ctx['risk'] = $risk;
        $ctx['component'] = $component;
        $ctx['group'] = $group;
        $ctx['source_moodle'] = $doc['source_moodle'];
        $ctx['target_moodle'] = $doc['target_moodle'];
        $ctx['islive'] = $doc['target'] === 'current';
        $ctx['statefilters'] = [];
        foreach (['added', 'changed', 'removed', 'hidden'] as $state) {
            $ctx['statefilters'][] = [
                'value' => $state,
                'label' => get_string('state_' . $state, 'local_siteconfigdiff'),
                'selected' => $status === $state,
            ];
        }
        $ctx['riskfilters'] = [];
        foreach (['low', 'manual', 'unknown'] as $item) {
            $ctx['riskfilters'][] = [
                'value' => $item,
                'label' => get_string('risk_' . $item, 'local_siteconfigdiff'),
                'selected' => $risk === $item,
            ];
        }
        $ctx['componentfilters'] = [];
        foreach (array_unique(array_column($doc['rows'], 'component')) as $c) {
            $ctx['componentfilters'][] = ['value' => $c, 'selected' => $component === $c];
        }
        $ctx['groupfilters'] = [];
        foreach (array_unique(array_column($doc['rows'], 'group')) as $g) {
            $ctx['groupfilters'][] = ['value' => $g, 'selected' => $group === $g];
        }
        return $ctx;
    }

    /**
     * Build the plan and audit view model.
     * @param int $id Argument.
     * @return array Result.
     */
    private function plan_context(int $id): array {
        $ctx = $this->base_context();
        $record = $this->repo->plan($id);
        $document = json_decode($record->document, true, 32, JSON_THROW_ON_ERROR);
        $ctx['id'] = $id;
        $ctx['status'] = get_string('state_' . $record->status, 'local_siteconfigdiff');
        $ctx['pending'] = $record->status === 'pending' && $ctx['canapply'];
        $ctx['completed'] = in_array($record->status, ['completed', 'partial'], true) && $ctx['canapply'];
        $ctx['kind'] = $document['kind'];
        $ctx['entries'] = [];
        foreach ($document['entries'] as $row) {
            $ctx['entries'][] = [
                'component' => $row['component'], 'name' => $row['name'],
                'oldvalue' => normalizer::display($row['expected']),
                'newvalue' => normalizer::display($row['desired']),
                'risk' => get_string('risk_' . $row['risk'], 'local_siteconfigdiff'),
            ];
        }
        $ctx['logs'] = array_map(static function($log) {
            return ['component' => $log->component, 'name' => $log->setting,
                'outcome' => get_string('state_' . $log->outcome, 'local_siteconfigdiff'),
                'reason' => get_string('reason_' . $log->reason, 'local_siteconfigdiff'),
                'author' => (int)$log->userid, 'created' => userdate($log->timecreated)];
        }, $this->repo->logs($id));
        return $ctx;
    }

    /**
     * Record an audit event.
     * @param string $action Argument.
     * @param int $objectid Argument.
     */
    private function emit(string $action, int $objectid): void {
        \local_siteconfigdiff\event\activity_logged::create([
            'context' => $this->context,
            // The event spans different tables; keep its record ID in other, not objectid.
            'other' => ['action' => $action, 'recordid' => $objectid],
        ])->trigger();
    }
}
