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
 * Audit trail persistence for flavour applications.
 *
 * Every call to flavour_manager::apply() records one row here. The rows
 * are immutable — we never update or delete audit records, they form
 * the single source of truth for "what did this site's flavour setup
 * look like over time". local_lernhive_configuration (R2) will read
 * from this table to render a configuration history.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * Writer/reader for the local_lernhive_flavour_apps table.
 */
class flavour_audit {

    /** Database table name. */
    const TABLE = 'local_lernhive_flavour_apps';

    /**
     * Record a flavour application.
     *
     * @param string $flavour Key of the flavour being applied.
     * @param string|null $previous Key of the previously active flavour, null on first apply.
     * @param int $appliedby User ID of the admin who triggered the apply.
     * @param array $settingsbefore Snapshot of config values before apply ([component][name] => value).
     * @param array $settingsafter Snapshot of config values after apply.
     * @param bool $overridesdetected True if any previously configured value was overwritten.
     * @return int The audit row ID.
     */
    public static function record(
        string $flavour,
        ?string $previous,
        int $appliedby,
        array $settingsbefore,
        array $settingsafter,
        bool $overridesdetected
    ): int {
        global $DB;

        $record = (object) [
            'flavour'            => $flavour,
            'previous_flavour'   => $previous,
            'applied_by'         => $appliedby,
            'timeapplied'        => time(),
            'settings_before'    => json_encode($settingsbefore, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'settings_after'     => json_encode($settingsafter,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'overrides_detected' => $overridesdetected ? 1 : 0,
        ];

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Return the most recent N audit records, newest first.
     *
     * @param int $limit Maximum number of records to return.
     * @return array Array of DB records with decoded JSON for settings_before/after.
     */
    public static function get_recent(int $limit = 20): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            null,
            'timeapplied DESC, id DESC',
            '*',
            0,
            max(1, $limit)
        );

        foreach ($records as $record) {
            $record->settings_before = self::decode_json_field($record->settings_before);
            $record->settings_after  = self::decode_json_field($record->settings_after);
        }

        return array_values($records);
    }

    /**
     * Return the most recent apply for a specific flavour, or null.
     *
     * @param string $flavour
     * @return object|null
     */
    public static function get_last_for_flavour(string $flavour): ?object {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            ['flavour' => $flavour],
            'timeapplied DESC, id DESC',
            '*',
            0,
            1
        );

        if (empty($records)) {
            return null;
        }

        $record = reset($records);
        $record->settings_before = self::decode_json_field($record->settings_before);
        $record->settings_after  = self::decode_json_field($record->settings_after);

        return $record;
    }

    /**
     * Total count of recorded apply events.
     *
     * @return int
     */
    public static function count_all(): int {
        global $DB;
        return (int) $DB->count_records(self::TABLE);
    }

    /**
     * Decode a JSON column into an associative array.
     *
     * @param string|null $raw
     * @return array
     */
    private static function decode_json_field(?string $raw): array {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
