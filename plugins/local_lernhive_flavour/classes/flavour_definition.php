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
 * Base class for a single LernHive flavour profile.
 *
 * A flavour profile describes a starting configuration for a LernHive
 * installation. It is intentionally a *recommendation*, not a lock —
 * admins may diverge from the flavour afterwards, and local_lernhive_flavour
 * tracks those divergences via the audit trail table.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract base for a flavour profile.
 *
 * Concrete profiles live in classes/profile/ and override the abstract
 * methods to describe themselves.
 */
abstract class flavour_definition {

    /** Stable maturity: profile is fully defined and release-ready. */
    const MATURITY_STABLE = 'stable';

    /** Experimental maturity: profile is a stub, defaults are best-guess. */
    const MATURITY_EXPERIMENTAL = 'experimental';

    /**
     * Short machine key for this flavour (e.g. 'school', 'lxp').
     *
     * Must match /^[a-z][a-z0-9_]{1,30}$/ and be unique across the registry.
     *
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Human-readable label, localised via get_string().
     *
     * @return string
     */
    abstract public function get_label(): string;

    /**
     * Longer description, localised via get_string().
     *
     * @return string
     */
    abstract public function get_description(): string;

    /**
     * Icon identifier. Currently a unicode emoji glyph that the picker
     * renders inside the flavour card. Kept as a plain string so we can
     * migrate to proper SVG icons later without breaking the interface.
     *
     * @return string
     */
    abstract public function get_icon(): string;

    /**
     * Maturity level of the profile. Stable profiles ship with full
     * R1 defaults; experimental ones inherit from school and show a
     * warning badge in the admin UI.
     *
     * @return string One of the MATURITY_* constants.
     */
    public function get_maturity(): string {
        return self::MATURITY_STABLE;
    }

    /**
     * The configuration keys this flavour manages.
     *
     * Return format:
     *   [
     *     'local_lernhive' => [
     *       'default_level' => 1,
     *       'show_levelbar' => 1,
     *       ...
     *     ],
     *     'theme_lernhive' => [...],
     *   ]
     *
     * Only keys listed here are touched on apply; everything else stays
     * untouched. This is what allows the flavour layer to coexist with
     * local_lernhive_configuration (R2) without stomping unrelated settings.
     *
     * @return array Map of component => [configkey => defaultvalue]
     */
    abstract public function get_defaults(): array;

    /**
     * Optional LernHive feature-level overrides for this flavour.
     *
     * Return format:
     *   [
     *     'mod_assign.create' => 2,
     *     'core.user.create' => null, // disabled
     *   ]
     *
     * The default implementation returns an empty map so existing profiles
     * remain backward compatible until explicit override packs are authored.
     *
     * @return array<string, int|null>
     */
    public function get_feature_overrides(): array {
        return [];
    }

    /**
     * Flat list of (component, key) pairs this flavour touches. Used by
     * flavour_manager to build diff snapshots.
     *
     * @return array List of ['component' => string, 'name' => string]
     */
    final public function get_managed_keys(): array {
        $keys = [];
        foreach ($this->get_defaults() as $component => $settings) {
            foreach (array_keys($settings) as $name) {
                $keys[] = ['component' => $component, 'name' => $name];
            }
        }
        return $keys;
    }

    /**
     * Convert to a plain array for templates and tests.
     *
     * @return array
     */
    final public function to_array(): array {
        return [
            'key'         => $this->get_key(),
            'label'       => $this->get_label(),
            'description' => $this->get_description(),
            'icon'        => $this->get_icon(),
            'maturity'    => $this->get_maturity(),
            'experimental' => $this->get_maturity() === self::MATURITY_EXPERIMENTAL,
            'defaults'    => $this->get_defaults(),
            'featureoverrides' => $this->get_feature_overrides(),
        ];
    }
}
