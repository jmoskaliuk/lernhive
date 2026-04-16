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

namespace local_lernhive\feature;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use local_lernhive\event\feature_override_changed;
use stdClass;

/**
 * DB adapter for feature-level overrides.
 *
 * Stores one effective row per feature in `local_lernhive_feature_overrides`.
 * Precedence is enforced on write:
 * - admin overrides always win and are never overwritten by flavor presets
 * - flavor preset writes are idempotent and update only flavor-owned rows
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class override_store {

    /** @var string DB table name. */
    private const TABLE = 'local_lernhive_feature_overrides';

    /** @var string Manual admin override source. */
    public const SOURCE_ADMIN = 'admin';

    /** @var string Flavor preset override source. */
    public const SOURCE_FLAVOR_PRESET = 'flavor_preset';

    /**
     * Static-only class.
     */
    private function __construct() {
    }

    /**
     * Get all effective overrides keyed by feature_id.
     *
     * @return array<string, int|null> Level 1..5, or null for "disabled".
     */
    public static function get_effective_levels(): array {
        $effective = [];
        foreach (self::get_effective_overrides() as $featureid => $record) {
            $effective[$featureid] = self::db_level_to_php($record->override_level);
        }
        return $effective;
    }

    /**
     * Get the effective override row for one feature.
     *
     * @param string $featureid Canonical feature ID.
     * @return stdClass|null DB row or null if no override exists.
     */
    public static function get_effective_override(string $featureid): ?stdClass {
        self::assert_feature_id($featureid);
        $overrides = self::get_effective_overrides();
        return $overrides[$featureid] ?? null;
    }

    /**
     * Insert or update a manual admin override.
     *
     * @param string   $featureid Canonical feature ID.
     * @param int|null $level 1..5, or null for disabled.
     * @param int|null $updatedby Admin user id, optional.
     */
    public static function set_admin_override(string $featureid, ?int $level, ?int $updatedby = null): void {
        self::assert_known_feature($featureid);
        self::assert_valid_level($level);

        global $DB;

        $now = time();
        $record = self::get_single_feature_row($featureid);
        $oldlevel = $record ? self::db_level_to_php($record->override_level) : null;
        $oldsource = $record ? (string) ($record->source ?? '') : '';
        $oldflavorid = $record ? (string) ($record->flavor_id ?? '') : '';
        if ($record && $oldsource === self::SOURCE_ADMIN && $oldlevel === $level) {
            return;
        }

        if ($record) {
            $record->override_level = $level;
            $record->source = self::SOURCE_ADMIN;
            $record->flavor_id = null;
            $record->updated_by = $updatedby;
            $record->timemodified = $now;
            $DB->update_record(self::TABLE, $record);
        } else {
            $insert = new stdClass();
            $insert->feature_id = $featureid;
            $insert->override_level = $level;
            $insert->source = self::SOURCE_ADMIN;
            $insert->flavor_id = null;
            $insert->timemodified = $now;
            $insert->updated_by = $updatedby;
            $DB->insert_record(self::TABLE, $insert);
        }

        registry::reset_cache();
        self::trigger_feature_override_changed_event(
            $featureid,
            $oldlevel,
            $level,
            self::SOURCE_ADMIN,
            null,
            $oldsource,
            $oldflavorid !== '' ? $oldflavorid : null,
            'set',
            true,
            $updatedby
        );
    }

    /**
     * Delete the override when it is admin-managed.
     *
     * @param string $featureid Canonical feature ID.
     */
    public static function clear_admin_override(string $featureid): void {
        self::assert_feature_id($featureid);

        global $DB;
        $record = self::get_single_feature_row($featureid);
        if ($record && (string) $record->source === self::SOURCE_ADMIN) {
            $oldlevel = self::db_level_to_php($record->override_level);
            $DB->delete_records(self::TABLE, ['id' => $record->id]);
            registry::reset_cache();
            self::trigger_feature_override_changed_event(
                $featureid,
                $oldlevel,
                null,
                self::SOURCE_ADMIN,
                null,
                self::SOURCE_ADMIN,
                null,
                'cleared',
                false,
                (int) ($record->updated_by ?? 0)
            );
        }
    }

    /**
     * Apply one flavor-preset override.
     *
     * Admin-owned rows are left untouched. Flavor-owned rows are updated
     * idempotently so repeating the same preset has no side effects.
     *
     * @param string   $featureid Canonical feature ID.
     * @param int|null $level 1..5, or null for disabled.
     * @param string   $flavorid Flavor key (e.g. "school", "lxp").
     * @return bool True when a row was inserted/updated, false when no-op.
     */
    public static function apply_flavor_preset(string $featureid, ?int $level, string $flavorid): bool {
        self::assert_known_feature($featureid);
        self::assert_valid_level($level);
        if ($flavorid === '') {
            throw new coding_exception('local_lernhive flavor_id must not be empty');
        }

        global $DB;

        $existing = self::get_single_feature_row($featureid);
        if ($existing && (string) $existing->source === self::SOURCE_ADMIN) {
            return false;
        }

        $existinglevel = $existing ? self::db_level_to_php($existing->override_level) : null;
        $oldsource = $existing ? (string) ($existing->source ?? '') : '';
        $oldflavorid = $existing ? (string) ($existing->flavor_id ?? '') : '';
        if ($existing
            && (string) $existing->source === self::SOURCE_FLAVOR_PRESET
            && $existinglevel === $level
            && (string) ($existing->flavor_id ?? '') === $flavorid
        ) {
            return false;
        }

        $now = time();
        if ($existing) {
            $existing->override_level = $level;
            $existing->source = self::SOURCE_FLAVOR_PRESET;
            $existing->flavor_id = $flavorid;
            $existing->updated_by = null;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        } else {
            $insert = new stdClass();
            $insert->feature_id = $featureid;
            $insert->override_level = $level;
            $insert->source = self::SOURCE_FLAVOR_PRESET;
            $insert->flavor_id = $flavorid;
            $insert->timemodified = $now;
            $insert->updated_by = null;
            $DB->insert_record(self::TABLE, $insert);
        }

        registry::reset_cache();
        self::trigger_feature_override_changed_event(
            $featureid,
            $existinglevel,
            $level,
            self::SOURCE_FLAVOR_PRESET,
            $flavorid,
            $oldsource,
            $oldflavorid !== '' ? $oldflavorid : null,
            'set',
            true,
            null
        );
        return true;
    }

    /**
     * Replace the complete flavor-owned override set with a new payload.
     *
     * The payload is interpreted as the full desired map for flavor-owned
     * rows. Any existing `flavor_preset` row not present in the payload is
     * removed. Admin-owned rows remain untouched.
     *
     * @param string $flavorid Flavor key (e.g. "school", "lxp").
     * @param array<string, int|null> $overrides Feature => level map.
     */
    public static function replace_flavor_preset_map(string $flavorid, array $overrides): void {
        if ($flavorid === '') {
            throw new coding_exception('local_lernhive flavor_id must not be empty');
        }

        $normalized = [];
        foreach ($overrides as $featureid => $level) {
            if (!is_string($featureid)) {
                throw new coding_exception('local_lernhive flavor preset feature_id must be string');
            }
            if (!is_int($level) && $level !== null) {
                throw new coding_exception(
                    "local_lernhive invalid flavor level type for '{$featureid}'"
                );
            }
            self::assert_known_feature($featureid);
            self::assert_valid_level($level);
            $normalized[$featureid] = $level;
        }

        global $DB;
        $rows = $DB->get_records(
            self::TABLE,
            ['source' => self::SOURCE_FLAVOR_PRESET],
            '',
            'id, feature_id, override_level, source, flavor_id, timemodified, updated_by'
        );
        foreach ($rows as $row) {
            $featureid = (string) $row->feature_id;
            if (array_key_exists($featureid, $normalized)) {
                continue;
            }

            $oldlevel = self::db_level_to_php($row->override_level);
            $oldflavorid = (string) ($row->flavor_id ?? '');

            $DB->delete_records(self::TABLE, ['id' => $row->id]);
            registry::reset_cache();
            self::trigger_feature_override_changed_event(
                $featureid,
                $oldlevel,
                null,
                self::SOURCE_FLAVOR_PRESET,
                $oldflavorid !== '' ? $oldflavorid : null,
                self::SOURCE_FLAVOR_PRESET,
                $oldflavorid !== '' ? $oldflavorid : null,
                'cleared',
                false,
                null
            );
        }

        foreach ($normalized as $featureid => $level) {
            self::apply_flavor_preset($featureid, $level, $flavorid);
        }
    }

    /**
     * Fetch effective rows with precedence admin > flavor_preset.
     *
     * The DB schema enforces one row per feature via unique index on feature_id,
     * but this method still applies precedence defensively.
     *
     * @return array<string, stdClass>
     */
    private static function get_effective_overrides(): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            null,
            'feature_id ASC, timemodified DESC, id DESC',
            'id, feature_id, override_level, source, flavor_id, timemodified, updated_by'
        );

        $effective = [];
        foreach ($records as $record) {
            $featureid = (string) $record->feature_id;
            if (!isset($effective[$featureid])) {
                $effective[$featureid] = $record;
                continue;
            }
            $current = $effective[$featureid];
            if (self::source_priority((string) $record->source) > self::source_priority((string) $current->source)) {
                $effective[$featureid] = $record;
            }
        }

        return $effective;
    }

    /**
     * Get the raw row for one feature (unique by schema).
     *
     * @param string $featureid Canonical feature ID.
     * @return stdClass|null
     */
    private static function get_single_feature_row(string $featureid): ?stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['feature_id' => $featureid], '*', IGNORE_MULTIPLE);
        return $record ?: null;
    }

    /**
     * Convert DB value to nullable int level.
     *
     * @param mixed $value DB value.
     * @return int|null
     */
    private static function db_level_to_php(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        $level = (int) $value;
        if ($level < definition::MIN_LEVEL || $level > definition::MAX_LEVEL) {
            return null;
        }
        return $level;
    }

    /**
     * Validate canonical feature id syntax.
     *
     * @param string $featureid Feature ID.
     */
    private static function assert_feature_id(string $featureid): void {
        if ($featureid === '') {
            throw new coding_exception('local_lernhive feature_id must not be empty');
        }
    }

    /**
     * Validate that the feature exists in the registry.
     *
     * @param string $featureid Feature ID.
     */
    private static function assert_known_feature(string $featureid): void {
        self::assert_feature_id($featureid);
        if (registry::get_feature($featureid) === null) {
            throw new coding_exception("local_lernhive unknown feature '{$featureid}'");
        }
    }

    /**
     * Validate a nullable override level.
     *
     * @param int|null $level 1..5 or null.
     */
    private static function assert_valid_level(?int $level): void {
        if ($level === null) {
            return;
        }
        if ($level < definition::MIN_LEVEL || $level > definition::MAX_LEVEL) {
            throw new coding_exception("local_lernhive invalid override level {$level}");
        }
    }

    /**
     * Precedence ranking for source values.
     *
     * @param string $source Row source.
     * @return int Higher wins.
     */
    private static function source_priority(string $source): int {
        return match ($source) {
            self::SOURCE_ADMIN => 20,
            self::SOURCE_FLAVOR_PRESET => 10,
            default => 0,
        };
    }

    /**
     * Fire the cross-plugin override-change event.
     *
     * @param string $featureid
     * @param int|null $oldlevel
     * @param int|null $newlevel
     * @param string $source New source after mutation.
     * @param string|null $flavorid New flavor id, if source is flavor preset.
     * @param string $oldsource Source before mutation.
     * @param string|null $oldflavorid Flavor id before mutation.
     * @param string $action `set` or `cleared`.
     * @param bool $hasoverride Whether an override row exists after mutation.
     * @param int|null $userid Optional user id to attribute.
     * @return void
     */
    private static function trigger_feature_override_changed_event(
        string $featureid,
        ?int $oldlevel,
        ?int $newlevel,
        string $source,
        ?string $flavorid,
        string $oldsource,
        ?string $oldflavorid,
        string $action,
        bool $hasoverride,
        ?int $userid
    ): void {
        $eventdata = [
            'context' => \context_system::instance(),
            'other' => [
                'feature_id' => $featureid,
                'old_level' => $oldlevel,
                'new_level' => $newlevel,
                'source' => $source,
                'flavor_id' => $flavorid,
                'old_source' => $oldsource,
                'old_flavor_id' => $oldflavorid,
                'action' => $action,
                'has_override' => $hasoverride,
            ],
        ];
        if ($userid !== null && $userid > 0) {
            $eventdata['userid'] = $userid;
        }

        $event = feature_override_changed::create($eventdata);
        $event->trigger();
    }
}
