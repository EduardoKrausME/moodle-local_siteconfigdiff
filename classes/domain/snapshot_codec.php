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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/domain/snapshot_codec.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/**
 * Bounded schema validation and tamper-evident JSON transport (not authenticity).
 */
final class snapshot_codec {
    /** MAX_BYTES safety limit. */
    public const MAX_BYTES = 1048576;
    /** MAX_COMPONENTS safety limit. */
    private const MAX_COMPONENTS = 2000;
    /** MAX_ENTRIES safety limit. */
    private const MAX_ENTRIES = 10000;

    /**
     * Seal a canonical snapshot.
     * @param array $document Parameter.
     * @return array Result.
     */
    public static function seal(array $document): array {
        unset($document['integrity']);
        $document = normalizer::sort_keys($document);
        $document['integrity'] = hash('sha256', normalizer::canonical($document));
        return normalizer::sort_keys($document);
    }

    /**
     * Encode a snapshot as JSON.
     * @param array $document Parameter.
     * @return string Result.
     */
    public static function encode(array $document): string {
        return json_encode(self::seal($document), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Validate and decode a snapshot.
     * @param string $input Parameter.
     * @return array Result.
     */
    public static function decode(string $input): array {
        if ($input === '' || strlen($input) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Invalid snapshot size.');
        }
        $doc = json_decode($input, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($doc) || array_is_list($doc) || ($doc['schema'] ?? null) !== 1 ||
            !is_string($doc['integrity'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $doc['integrity'])) {
            throw new \InvalidArgumentException('Invalid snapshot schema or integrity metadata.');
        }
        $expectedkeys = ['components', 'generated_at', 'installation', 'integrity', 'moodle', 'plugins', 'schema'];
        $actualkeys = array_keys($doc);
        sort($actualkeys, SORT_STRING);
        if ($actualkeys !== $expectedkeys) {
            throw new \InvalidArgumentException('Unexpected snapshot fields.');
        }
        $integrity = $doc['integrity'];
        if (!hash_equals(self::seal($doc)['integrity'], $integrity)) {
            throw new \InvalidArgumentException('Snapshot checksum mismatch.');
        }
        foreach (['installation', 'moodle', 'generated_at', 'plugins', 'components'] as $key) {
            if (!array_key_exists($key, $doc)) {
                throw new \InvalidArgumentException('Snapshot required field missing.');
            }
        }
        if (!is_string($doc['installation']) || !preg_match('/^[a-f0-9]{64}$/D', $doc['installation']) ||
            !is_string($doc['moodle']) || strlen($doc['moodle']) > 60 ||
            !is_int($doc['generated_at']) || !is_array($doc['plugins']) ||
            !is_array($doc['components']) || count($doc['components']) > self::MAX_COMPONENTS ||
            count($doc['plugins']) > self::MAX_COMPONENTS) {
            throw new \InvalidArgumentException('Snapshot metadata is invalid.');
        }
        $entries = 0;
        foreach ($doc['plugins'] as $component => $plugin) {
            self::check_name($component);
            if (!is_array($plugin) || !is_string($plugin['version'] ?? null) ||
                strlen($plugin['version']) > 40 || !is_string($plugin['release'] ?? null) ||
                strlen($plugin['release']) > 80 || count($plugin) !== 2) {
                throw new \InvalidArgumentException('Invalid plugin manifest.');
            }
        }
        foreach ($doc['components'] as $component => $settings) {
            self::check_name($component);
            if (!is_array($settings)) {
                throw new \InvalidArgumentException('Invalid component settings.');
            }
            foreach ($settings as $name => $entry) {
                self::check_name($name);
                if (++$entries > self::MAX_ENTRIES || !is_array($entry)) {
                    throw new \InvalidArgumentException('Too many or invalid settings.');
                }
                if (($entry['kind'] ?? '') === 'redacted') {
                    if (($entry['present'] ?? null) !== true || count($entry) !== 2) {
                        throw new \InvalidArgumentException('Invalid redacted setting.');
                    }
                    continue;
                }
                if (($entry['kind'] ?? '') !== 'value' || !policy::readable($component, $name) ||
                    !array_key_exists('type', $entry) || !array_key_exists('value', $entry) ||
                    count($entry) !== 3) {
                    throw new \InvalidArgumentException('Unapproved clear-text setting in snapshot.');
                }
                $valid = match ($entry['type']) {
                    'null' => $entry['value'] === null,
                    'string' => is_string($entry['value']) && strlen($entry['value']) <= 4000,
                    'boolean' => is_bool($entry['value']),
                    'number' => is_int($entry['value']) || is_float($entry['value']),
                    default => false,
                };
                if (!$valid) {
                    throw new \InvalidArgumentException('Setting type mismatch.');
                }
            }
        }
        return $doc;
    }

    /**
     * Validate the component or setting name.
     * @param mixed $name Parameter.
     */
    private static function check_name($name): void {
        if (!is_string($name) || strlen($name) > 150 ||
            !preg_match('/^[a-z][a-z0-9_]*$/D', $name)) {
            throw new \InvalidArgumentException('Invalid component or setting name.');
        }
    }
}
