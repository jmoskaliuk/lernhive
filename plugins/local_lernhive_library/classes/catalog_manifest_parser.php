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
 * Shared manifest parser for local_lernhive_library catalog sources.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

defined('MOODLE_INTERNAL') || die();

/**
 * Parse catalog-entry rows from JSON payloads.
 */
final class catalog_manifest_parser {
    /**
     * Parse catalog JSON payload into value objects.
     *
     * Accepted structures:
     * - top-level array of entries
     * - object with `entries` array
     *
     * @param string $manifestjson
     * @param string $sourcelabel
     * @return catalog_entry[]
     */
    public function parse_json(string $manifestjson, string $sourcelabel = 'manifest'): array {
        $manifestjson = trim($manifestjson);
        if ($manifestjson === '') {
            return [];
        }

        $decoded = json_decode($manifestjson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            debugging(
                'LernHive Library ' . $sourcelabel . ' is not valid JSON: ' . json_last_error_msg(),
                DEBUG_DEVELOPER
            );
            return [];
        }

        $rows = $decoded;
        if (array_key_exists('entries', $decoded) && is_array($decoded['entries'])) {
            $rows = $decoded['entries'];
        }

        $entries = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                debugging(
                    'LernHive Library ' . $sourcelabel . ' entry at index ' . $index .
                    ' is not an object and was ignored.',
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $updated = $this->normalise_updated($row['updated'] ?? null);
            if ($updated === null) {
                debugging(
                    'LernHive Library ' . $sourcelabel . ' entry at index ' . $index .
                    ' has an invalid "updated" value and was ignored.',
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $sourcecourseid = $this->normalise_source_course_id($row['sourcecourseid'] ?? null);

            try {
                $entries[] = new catalog_entry(
                    id: (string) ($row['id'] ?? ''),
                    title: (string) ($row['title'] ?? ''),
                    description: (string) ($row['description'] ?? ''),
                    version: (string) ($row['version'] ?? ''),
                    updated: $updated,
                    language: (string) ($row['language'] ?? ''),
                    sourcecourseid: $sourcecourseid,
                );
            } catch (\coding_exception $e) {
                debugging(
                    'LernHive Library ' . $sourcelabel . ' entry at index ' . $index .
                    ' is invalid and was ignored: ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        return $entries;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function normalise_updated(mixed $value): ?int {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if (ctype_digit($trimmed)) {
                return (int) $trimmed;
            }

            $parsed = strtotime($trimmed);
            if ($parsed !== false && $parsed >= 0) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function normalise_source_course_id(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $parsed = (int) $trimmed;
                return $parsed > 0 ? $parsed : null;
            }
        }

        return null;
    }
}

