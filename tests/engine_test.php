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
 * tests/engine_test.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff;

use local_siteconfigdiff\domain\applier;
use local_siteconfigdiff\domain\comparator;
use local_siteconfigdiff\domain\normalizer;
use local_siteconfigdiff\domain\planner;
use local_siteconfigdiff\domain\policy;
use local_siteconfigdiff\domain\snapshot_codec;

/**
 * Regression cases for canonicalization, import policy and optimistic concurrency.
 *
 * @covers \local_siteconfigdiff\domain\applier
 * @covers \local_siteconfigdiff\domain\comparator
 * @covers \local_siteconfigdiff\domain\normalizer
 * @covers \local_siteconfigdiff\domain\planner
 * @covers \local_siteconfigdiff\domain\policy
 * @covers \local_siteconfigdiff\domain\snapshot_codec
 */
final class engine_test extends \advanced_testcase {
    /**
     * Handle test normalization preserves absent null and empty.
     */
    public function test_normalization_preserves_absent_null_and_empty(): void {
        $this->assertTrue(normalizer::equal(['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1]));
        $this->assertFalse(normalizer::equal(null, ''));
        $this->assertFalse(normalizer::equal(['kind' => 'value', 'type' => 'string', 'value' => '1'],
            ['kind' => 'value', 'type' => 'number', 'value' => 1]));
        $this->assertSame('[absent]', normalizer::display(null));
        $this->assertSame('[null]', normalizer::display(['kind' => 'value', 'type' => 'null', 'value' => null]));
        $this->assertSame('[empty string]', normalizer::display(['kind' => 'value', 'type' => 'string', 'value' => '']));
    }

    /**
     * Create an isolated snapshot for assertions.
     * @param string $language Argument.
     * @return array Result.
     */
    private function fixture(string $language = 'en'): array {
        return snapshot_codec::seal([
            'schema' => 1,
            'installation' => str_repeat('a', 64),
            'moodle' => '4.5',
            'generated_at' => 100,
            'plugins' => ['local_example' => ['version' => '2026010100', 'release' => '1.0']],
            'components' => [
                'core' => ['lang' => policy::entry('core', 'lang', $language),
                    'smtpsecret' => policy::entry('core', 'smtpsecret', 'TOPSECRET')],
                'local_example' => ['apikey' => policy::entry('local_example', 'apikey', 'TOPSECRET')],
            ],
        ]);
    }

    /**
     * Handle test snapshot does not export credentials and detects tampering.
     */
    public function test_snapshot_does_not_export_credentials_and_detects_tampering(): void {
        $encoded = snapshot_codec::encode($this->fixture());
        $this->assertStringNotContainsString('TOPSECRET', $encoded);
        $this->assertSame('redacted', snapshot_codec::decode($encoded)['components']['core']['smtpsecret']['kind']);
        $this->expectException(\InvalidArgumentException::class);
        snapshot_codec::decode(str_replace('"value": "en"', '"value": "pt_br"', $encoded));
    }

    /**
     * Handle test import rejects unreviewed plaintext.
     */
    public function test_import_rejects_unreviewed_plaintext(): void {
        $doc = $this->fixture();
        $doc['components']['core']['smtpsecret'] = ['kind' => 'value', 'type' => 'string', 'value' => 'LEAK'];
        $this->expectException(\InvalidArgumentException::class);
        snapshot_codec::decode(snapshot_codec::encode($doc));
    }

    /**
     * Handle test differences and redacted unknowns.
     */
    public function test_differences_and_redacted_unknowns(): void {
        $from = $this->fixture('pt_br');
        $to = $this->fixture('en');
        $diff = (new comparator())->compare($from, $to, true);
        $this->assertCount(3, $diff['rows']);
        $this->assertSame('changed', $diff['rows'][0]['status']);
        $this->assertTrue($diff['rows'][0]['applicable']);
        $this->assertSame('hidden', $diff['rows'][1]['status']);
        $this->assertFalse($diff['rows'][1]['applicable']);
    }

    /**
     * Handle test application plan blocks unreviewed values.
     */
    public function test_application_plan_blocks_unreviewed_values(): void {
        $diff = (new comparator())->compare($this->fixture('pt_br'), $this->fixture('en'), true);
        $plan = (new planner())->create($diff, [0]);
        $this->assertCount(1, $plan['entries']);
        $this->assertSame('en', $plan['entries'][0]['expected']['value']);
        $this->expectException(\InvalidArgumentException::class);
        (new planner())->create($diff, [1]);
    }

    /**
     * Handle test manifest detects missing plugin without setting differences.
     */
    public function test_manifest_detects_missing_plugin_without_setting_differences(): void {
        $source = $this->fixture();
        $target = $this->fixture();
        $source['components']['local_example'] = [];
        unset($target['plugins']['local_example'], $target['components']['local_example']);
        $diff = (new comparator())->compare($source, $target, false);
        $this->assertSame('missing_destination', $diff['pluginchanges'][0]['status']);
        foreach ($diff['rows'] as $row) {
            $this->assertNotSame('local_example', $row['component']);
        }
    }

    /**
     * Handle test rejects extra document properties and oversized inputs.
     */
    public function test_rejects_extra_document_properties_and_oversized_inputs(): void {
        $document = $this->fixture();
        $document['unreviewed'] = 'unexpected';
        try {
            snapshot_codec::decode(snapshot_codec::encode($document));
            $this->fail('Additional top-level properties must be rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->expectException(\InvalidArgumentException::class);
        snapshot_codec::decode(str_repeat('a', snapshot_codec::MAX_BYTES + 1));
    }

    /**
     * Handle test apply checks stale destination.
     */
    public function test_apply_checks_stale_destination(): void {
        global $USER, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowedappliers', (string)$USER->id, 'local_siteconfigdiff');
        set_config('lang', 'en');
        $plan = [
            'schema' => 1, 'kind' => 'apply', 'installation' => hash('sha256', $CFG->wwwroot),
            'entries' => [[
                'component' => 'core', 'name' => 'lang',
                'expected' => policy::entry('core', 'lang', 'pt_br'),
                'desired' => policy::entry('core', 'lang', 'fr'),
            ]],
        ];
        $audits = [];
        $summary = (new applier())->apply($plan, function(...$args) use (&$audits) {
            $audits[] = $args;
        });
        $this->assertSame(1, $summary['stale']);
        $this->assertSame('en', get_config(null, 'lang'));
        $this->assertSame('stale', $audits[0][2]);
        $plan['entries'][0]['expected'] = policy::entry('core', 'lang', 'en');
        $summary = (new applier())->apply($plan, function(): void {
            // An audit callback is required, but not inspected by this assertion.
        });
        $this->assertSame(1, $summary['applied']);
        $this->assertSame('fr', get_config(null, 'lang'));
    }
}
