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
 * classes/domain/normalizer.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/**
 * Deterministic comparison of documented JSON value types.
 */
final class normalizer {
    /**
     * Sort structured configuration values recursively.
     * @param mixed $value Parameter.
     */
    public static function sort_keys($value) {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $child) {
            $value[$key] = self::sort_keys($child);
        }
        return $value;
    }

    /**
     * Build a canonical representation.
     * @param mixed $value Parameter.
     * @return string Result.
     */
    public static function canonical($value): string {
        $encoded = json_encode(self::sort_keys($value), JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        return $encoded;
    }

    /**
     * Compare normalized configuration entries.
     * @param mixed $a Parameter.
     * @param mixed $b Parameter.
     * @return bool Result.
     */
    public static function equal($a, $b): bool {
        return self::canonical($a) === self::canonical($b);
    }

    /**
     * Render a sanitized setting value.
     * @return string Result.
     */
    public static function display(?array $entry): string {
        if ($entry === null) {
            return '[absent]';
        }
        if (($entry['kind'] ?? '') === 'redacted') {
            return '[redacted]';
        }
        if (($entry['type'] ?? '') === 'null') {
            return '[null]';
        }
        if (($entry['type'] ?? '') === 'string' && $entry['value'] === '') {
            return '[empty string]';
        }
        return self::canonical($entry['value']);
    }
}
