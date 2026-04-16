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
 * Flavour manager — orchestrates apply, diff and lookup of flavour profiles.
 *
 * Responsibilities:
 * - expose the currently active flavour
 * - compute a diff between a target flavour and the current site config
 * - apply a flavour (writes config + audit trail + triggers event)
 *
 * Intentionally stateless: every method is static, no mutable fields.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * Flavour manager façade.
 */
class flavour_manager {

    /** Config component where we persist the active flavour key. */
    const COMPONENT = 'local_lernhive_flavour';

    /** Config key for the active flavour. */
    const CONFIG_ACTIVE = 'active_flavour';

    /**
     * Get the currently active flavour key.
     *
     * Falls back to the registry default when no flavour has been stored
     * yet (fresh install, or tests with cleared config).
     *
     * @return string
     */
    public static function get_active(): string {
        $stored = get_config(self::COMPONENT, self::CONFIG_ACTIVE);
        if (is_string($stored) && $stored !== '' && flavour_registry::exists($stored)) {
            return $stored;
        }
        return flavour_registry::DEFAULT_FLAVOUR;
    }

    /**
     * Apply a flavour.
     *
     * Writes all managed config keys, records an audit row and triggers
     * the flavour_applied event. The admin is free to subsequently
     * override any key — the audit trail captures the diff so that
     * local_lernhive_configuration (R2) can surface it.
     *
     * @param string $key Flavour key.
     * @param int|null $appliedby User ID of the admin performing the apply.
     *                            Defaults to the current $USER->id.
     * @return \stdClass Result object with fields:
     *                   - flavour (string)
     *                   - previous (string|null)
     *                   - before (array)
     *                   - after (array)
     *                   - overrides_detected (bool)
     *                   - audit_id (int)
     * @throws \invalid_parameter_exception If the flavour key is unknown.
     */
    public static function apply(string $key, ?int $appliedby = null): \stdClass {
        global $USER;

        $profile = flavour_registry::get($key);
        if ($profile === null) {
            throw new \invalid_parameter_exception("Unknown flavour key: {$key}");
        }

        $previous = self::get_active();

        // Capture the "before" snapshot from exactly the keys this profile manages.
        $before = self::snapshot_keys($profile);

        // Apply every managed (component, name) => value tuple.
        foreach ($profile->get_defaults() as $component => $settings) {
            foreach ($settings as $name => $value) {
                set_config($name, $value, $component);
            }
        }

        // Apply feature-level flavour overrides in local_lernhive.
        if (class_exists(\local_lernhive\feature\registry::class)) {
            \local_lernhive\feature\registry::apply_flavor_preset(
                $key,
                $profile->get_feature_overrides()
            );
        }

        // Persist the active flavour key itself.
        set_config(self::CONFIG_ACTIVE, $key, self::COMPONENT);

        // Capture the "after" snapshot so auditors can diff without rerunning.
        $after = self::snapshot_keys($profile);

        $overrides = self::detect_overrides($before, $after);

        $auditid = flavour_audit::record(
            $key,
            $previous !== $key ? $previous : $previous,
            $appliedby ?? (int) ($USER->id ?? 0),
            $before,
            $after,
            $overrides
        );

        // Fire the event so other plugins (esp. local_lernhive_configuration
        // in R2) can react to flavour changes.
        $event = \local_lernhive_flavour\event\flavour_applied::create([
            'context' => \core\context\system::instance(),
            'objectid' => $auditid,
            'other' => [
                'flavour'  => $key,
                'previous' => $previous,
                'overrides_detected' => $overrides,
            ],
        ]);
        $event->trigger();

        return (object) [
            'flavour'            => $key,
            'previous'           => $previous,
            'before'             => $before,
            'after'              => $after,
            'overrides_detected' => $overrides,
            'audit_id'           => $auditid,
        ];
    }

    /**
     * Compute the diff between the target flavour's defaults and the
     * current live site config.
     *
     * Used by the admin picker to show a confirm dialog before applying.
     *
     * @param string $key Flavour key to simulate.
     * @return array List of diff entries:
     *               [
     *                 [
     *                   'component' => 'local_lernhive',
     *                   'name'      => 'show_levelbar',
     *                   'current'   => '1',
     *                   'target'    => '0',
     *                   'changes'   => true,
     *                 ], ...
     *               ]
     * @throws \invalid_parameter_exception If the flavour key is unknown.
     */
    public static function diff(string $key): array {
        $profile = flavour_registry::get($key);
        if ($profile === null) {
            throw new \invalid_parameter_exception("Unknown flavour key: {$key}");
        }

        $diff = [];
        foreach ($profile->get_defaults() as $component => $settings) {
            foreach ($settings as $name => $targetvalue) {
                $current = self::get_config_raw($component, $name);
                $diff[] = [
                    'component' => $component,
                    'name'      => $name,
                    'current'   => $current,
                    'target'    => (string) $targetvalue,
                    'changes'   => (string) $current !== (string) $targetvalue,
                ];
            }
        }
        return $diff;
    }

    /**
     * Does applying this flavour overwrite at least one existing setting?
     *
     * @param string $key
     * @return bool
     * @throws \invalid_parameter_exception
     */
    public static function has_pending_overrides(string $key): bool {
        foreach (self::diff($key) as $entry) {
            // Only treat as an override when the value was previously SET by an
            // admin (current !== null) AND would be changed by applying this flavour.
            // A null current means the key has never been configured — applying the
            // flavour is a first-time seed, not a stomping of existing state.
            // This matches the logic in detect_overrides() which was designed with
            // the same intent: "first-ever apply on a fresh site returns false".
            if ($entry['current'] !== null && $entry['changes']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a snapshot of the current values of all keys this profile manages.
     *
     * @param flavour_definition $profile
     * @return array Nested [component][name] => stringvalue, or null if unset.
     */
    private static function snapshot_keys(flavour_definition $profile): array {
        $snapshot = [];
        foreach ($profile->get_defaults() as $component => $settings) {
            $snapshot[$component] = [];
            foreach (array_keys($settings) as $name) {
                $snapshot[$component][$name] = self::get_config_raw($component, $name);
            }
        }
        return $snapshot;
    }

    /**
     * Read a single config value as a string, or null if not set.
     *
     * Wraps get_config() because the global call returns `false` for
     * missing keys — we'd rather distinguish "not set" (null) from
     * "set to false/empty string" (''), and consistently type as string.
     *
     * @param string $component
     * @param string $name
     * @return string|null
     */
    private static function get_config_raw(string $component, string $name): ?string {
        $value = get_config($component, $name);
        if ($value === false) {
            return null;
        }
        return (string) $value;
    }

    /**
     * Compare a before/after snapshot and return whether any value actually
     * changed AND the before value was non-null (i.e. an admin had already
     * set something that we then overwrote).
     *
     * A first-ever apply on a fresh site will return false because every
     * `before` value is null — that's the desired behaviour: the confirm
     * dialog should only fire if we are stomping on real prior state.
     *
     * @param array $before
     * @param array $after
     * @return bool
     */
    private static function detect_overrides(array $before, array $after): bool {
        foreach ($before as $component => $keys) {
            foreach ($keys as $name => $oldvalue) {
                $newvalue = $after[$component][$name] ?? null;
                if ($oldvalue !== null && $oldvalue !== $newvalue) {
                    return true;
                }
            }
        }
        return false;
    }
}
