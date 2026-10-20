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
 * classes/repository/history.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\repository;

use local_siteconfigdiff\domain\snapshot_codec;

/**
 * Database operations for the four persistence aggregates.
 */
final class history {
    /**
     * Persist a sanitized snapshot.
     * @param string $name Parameter.
     * @param array $doc Parameter.
     * @return int Result.
     */
    public function store_snapshot(string $name, array $doc): int {
        global $DB, $USER;
        $serialized = snapshot_codec::encode($doc);
        snapshot_codec::decode($serialized); // Check the final serialized representation and bounds.
        return $DB->insert_record('local_siteconfigdiff_snap', (object)[
            'name' => substr($name, 0, 255), 'origin' => $doc['installation'],
            'integrityhash' => $doc['integrity'], 'document' => $serialized,
            'userid' => $USER->id, 'timecreated' => time(),
        ]);
    }

    /**
     * Return saved snapshots.
     * @return array Result.
     */
    public function snapshots(): array {
        global $DB;
        return array_values($DB->get_records('local_siteconfigdiff_snap', null, 'id DESC',
            'id,name,origin,integrityhash,userid,timecreated', 0, 100));
    }

    /**
     * Load a snapshot by identifier.
     * @param int $id Parameter.
     * @return array Result.
     */
    public function snapshot(int $id): array {
        global $DB;
        $record = $DB->get_record('local_siteconfigdiff_snap', ['id' => $id], '*', MUST_EXIST);
        $doc = snapshot_codec::decode($record->document);
        if (!hash_equals($record->integrityhash, $doc['integrity'])) {
            throw new \InvalidArgumentException('Stored snapshot checksum mismatch.');
        }
        return $doc;
    }

    /**
     * Persist a configuration comparison.
     * @param int $left Parameter.
     * @param int $right Parameter.
     * @param array $doc Parameter.
     * @return int Result.
     */
    public function store_comparison(int $left, int $right, array $doc): int {
        global $DB, $USER;
        return $DB->insert_record('local_siteconfigdiff_diff', (object)[
            'leftid' => $left, 'rightid' => $right,
            'document' => json_encode($doc, JSON_THROW_ON_ERROR),
            'userid' => $USER->id, 'timecreated' => time(),
        ]);
    }

    /**
     * Return saved comparisons.
     * @return array Result.
     */
    public function comparisons(): array {
        global $DB;
        return array_values($DB->get_records('local_siteconfigdiff_diff', null, 'id DESC',
            'id,leftid,rightid,userid,timecreated', 0, 100));
    }

    /**
     * Load a comparison.
     * @param int $id Parameter.
     * @return array Result.
     */
    public function comparison(int $id): array {
        global $DB;
        $record = $DB->get_record('local_siteconfigdiff_diff', ['id' => $id], '*', MUST_EXIST);
        return json_decode($record->document, true, 32, JSON_THROW_ON_ERROR);
    }

    /**
     * Persist an application plan.
     * @param int $comparisonid Parameter.
     * @param array $doc Parameter.
     * @return int Result.
     */
    public function store_plan(int $comparisonid, array $doc): int {
        global $DB, $USER;
        return $DB->insert_record('local_siteconfigdiff_plan', (object)[
            'comparisonid' => $comparisonid,
            'document' => json_encode($doc, JSON_THROW_ON_ERROR),
            'status' => 'pending', 'userid' => $USER->id,
            'timecreated' => time(), 'timeapplied' => 0,
        ]);
    }

    /**
     * Return saved application plans.
     * @return array Result.
     */
    public function plans(): array {
        global $DB;
        return array_values($DB->get_records('local_siteconfigdiff_plan', null, 'id DESC',
            'id,comparisonid,status,userid,timecreated,timeapplied', 0, 100));
    }

    /**
     * Load a saved application plan.
     * @param int $id Parameter.
     * @return \stdClass Result.
     */
    public function plan(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_siteconfigdiff_plan', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Record an audit entry.
     * @param int $planid Parameter.
     * @param string $component Parameter.
     * @param string $setting Parameter.
     * @param string $outcome Parameter.
     * @param string $reason Parameter.
     * @param array $before Parameter.
     * @param array $after Parameter.
     */
    public function log(int $planid, string $component, string $setting, string $outcome,
            string $reason, array $before, array $after): void {
        global $DB, $USER;
        $DB->insert_record('local_siteconfigdiff_log', (object)[
            'planid' => $planid, 'component' => substr($component, 0, 100),
            'setting' => substr($setting, 0, 150), 'outcome' => $outcome,
            'reason' => substr($reason, 0, 255),
            'beforejson' => json_encode($before, JSON_THROW_ON_ERROR),
            'afterjson' => json_encode($after, JSON_THROW_ON_ERROR),
            'userid' => $USER->id, 'timecreated' => time(),
        ]);
    }

    /**
     * Return audit entries for a plan.
     * @param int $planid Parameter.
     * @return array Result.
     */
    public function logs(int $planid): array {
        global $DB;
        return array_values($DB->get_records('local_siteconfigdiff_log', ['planid' => $planid], 'id ASC'));
    }
}
