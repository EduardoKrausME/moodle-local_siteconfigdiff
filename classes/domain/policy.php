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
 * classes/domain/policy.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_siteconfigdiff\domain;

/**
 * Conservative policy for observation and intentional writes.
 */
final class policy {
    /**
     * Only reviewed core values can leave the originating site in clear text.
     */
    private const READABLE = [
        'core' => ['lang', 'country', 'timezone', 'theme', 'calendar'],
    ];
    /**
     * Write allowlist is smaller than the observation allowlist.
     */
    private const WRITABLE = ['lang', 'country', 'timezone'];

    /**
     * Check whether a setting can leave the site in clear text.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @return bool Result.
     */
    public static function readable(string $component, string $key): bool {
        return in_array($key, self::READABLE[$component] ?? [], true);
    }

    /**
     * Check whether a setting may be applied.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @return bool Result.
     */
    public static function writable(string $component, string $key): bool {
        return $component === 'core' && in_array($key, self::WRITABLE, true);
    }

    /**
     * Validate a writable setting value.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @param mixed $value Parameter.
     * @return bool Result.
     */
    public static function validate_value(string $component, string $key, $value): bool {
        if (!self::writable($component, $key) || !is_string($value)) {
            return false;
        }
        if ($key === 'lang') {
            return (bool)preg_match('/^[a-z]{2,3}(?:_[a-z]{2})?$/D', $value);
        }
        if ($key === 'country') {
            return $value === '' || (bool)preg_match('/^[A-Z]{2}$/D', $value);
        }
        if ($key === 'timezone') {
            return strlen($value) <= 80 && (bool)preg_match('/^[a-zA-Z0-9_+\/-]+$/D', $value);
        }
        return false;
    }

    /**
     * Classify the risk of a setting.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @return string Result.
     */
    public static function risk(string $component, string $key): string {
        if (!self::readable($component, $key)) {
            return 'unknown';
        }
        if (self::writable($component, $key)) {
            return 'low';
        }
        return 'manual';
    }

    /**
     * Describe the impact of a configuration change.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @return string Result.
     */
    public static function impact(string $component, string $key): string {
        if (!self::readable($component, $key)) {
            return 'unreviewed';
        }
        return match ($key) {
            'lang' => 'language',
            'country' => 'locale',
            'timezone' => 'time',
            'theme' => 'theme',
            'calendar' => 'calendar',
            default => 'unknown',
        };
    }

    /**
     * Sanitize a setting before snapshot storage.
     * @param string $component Parameter.
     * @param string $key Parameter.
     * @param mixed $value Parameter.
     * @return array Result.
     */
    public static function entry(string $component, string $key, $value): array {
        if (!self::readable($component, $key)) {
            return ['kind' => 'redacted', 'present' => true];
        }
        // Moodle config values are persisted as strings. Do not equate null, '' and absent.
        if ($value === null) {
            return ['kind' => 'value', 'type' => 'null', 'value' => null];
        }
        if (is_bool($value)) {
            return ['kind' => 'value', 'type' => 'boolean', 'value' => $value];
        }
        if (is_int($value) || is_float($value)) {
            return ['kind' => 'value', 'type' => 'number', 'value' => $value];
        }
        if (is_string($value) && strlen($value) <= 4000) {
            return ['kind' => 'value', 'type' => 'string', 'value' => $value];
        }
        return ['kind' => 'redacted', 'present' => true];
    }
}
