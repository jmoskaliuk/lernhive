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

/**
 * Immutable value object describing a single LernHive feature.
 *
 * A feature is the atomic unit that the level system gates on. Each feature
 * binds a canonical identifier (e.g. 'mod_assign.create') to:
 *   - the default LernHive level at which it unlocks,
 *   - the Moodle core capability that must hold for the user (runtime gate),
 *   - a language string for the admin UI,
 *   - an authoring category hint (for tour packs) and an optional flavor hint.
 *
 * Instances are constructed by {@see registry} only; no DB access lives here.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class definition {

    /** @var int Lowest supported level (Explorer). */
    public const MIN_LEVEL = 1;

    /** @var int Highest supported level (Master). */
    public const MAX_LEVEL = 5;

    /**
     * Build a feature definition.
     *
     * @param string      $featureid          Canonical feature ID (e.g. 'mod_assign.create'). Non-empty.
     * @param int         $defaultlevel       Default level 1..5 at which the feature unlocks.
     * @param string      $requiredcapability Moodle capability string that must hold in the relevant context.
     * @param string      $langkey            Language string key for the admin UI description.
     * @param string      $categoryhint       Authoring category hint used by the onboarding tour packs.
     * @param string|null $flavorhint         Optional flavor hint ('schule', 'lxp', 'academy', ...).
     *
     * @throws coding_exception If any argument is empty or out of range.
     */
    public function __construct(
        public readonly string $featureid,
        public readonly int $defaultlevel,
        public readonly string $requiredcapability,
        public readonly string $langkey,
        public readonly string $categoryhint,
        public readonly ?string $flavorhint = null,
    ) {
        if ($featureid === '') {
            throw new coding_exception('local_lernhive feature_id must not be empty');
        }
        if ($defaultlevel < self::MIN_LEVEL || $defaultlevel > self::MAX_LEVEL) {
            throw new coding_exception(
                "local_lernhive feature '{$featureid}' has invalid default level {$defaultlevel}"
            );
        }
        if ($requiredcapability === '') {
            throw new coding_exception(
                "local_lernhive feature '{$featureid}' must declare a required capability"
            );
        }
        if ($langkey === '') {
            throw new coding_exception(
                "local_lernhive feature '{$featureid}' must declare a lang_key"
            );
        }
        if ($categoryhint === '') {
            throw new coding_exception(
                "local_lernhive feature '{$featureid}' must declare a category_hint"
            );
        }
    }
}
