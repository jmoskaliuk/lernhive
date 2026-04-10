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
 * Registry of available LernHive flavour profiles.
 *
 * Kept deliberately static — the profile set is part of the plugin code,
 * not runtime data. If a customer wants to add a custom flavour, the
 * sanctioned route in R2 will be a subplugin hook, not DB records.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour;

use local_lernhive_flavour\profile\school_profile;
use local_lernhive_flavour\profile\lxp_profile;
use local_lernhive_flavour\profile\highered_profile;
use local_lernhive_flavour\profile\corporate_profile;

defined('MOODLE_INTERNAL') || die();

/**
 * Lookup and listing of flavour profiles.
 */
class flavour_registry {

    /** Default flavour used when nothing has been applied yet. */
    const DEFAULT_FLAVOUR = 'school';

    /** @var array<string, flavour_definition>|null Cached instances, lazy-built. */
    private static ?array $cache = null;

    /**
     * Return all profiles keyed by flavour key.
     *
     * Order matters for the picker UI: school first (default), then LXP
     * (other R1 scope), then the experimental stubs.
     *
     * @return array<string, flavour_definition>
     */
    public static function all(): array {
        if (self::$cache === null) {
            self::$cache = [
                'school'    => new school_profile(),
                'lxp'       => new lxp_profile(),
                'highered'  => new highered_profile(),
                'corporate' => new corporate_profile(),
            ];
        }
        return self::$cache;
    }

    /**
     * Return a single profile by key or null if unknown.
     *
     * @param string $key
     * @return flavour_definition|null
     */
    public static function get(string $key): ?flavour_definition {
        return self::all()[$key] ?? null;
    }

    /**
     * Whether the given key matches a registered profile.
     *
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool {
        return isset(self::all()[$key]);
    }

    /**
     * List of all registered flavour keys.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys(self::all());
    }

    /**
     * Reset the internal cache. Test-only helper — production code should
     * not need to call this.
     *
     * @return void
     */
    public static function reset_cache(): void {
        self::$cache = null;
    }
}
