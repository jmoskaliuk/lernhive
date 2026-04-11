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
 * Card registry — decides which content paths are exposed in ContentHub.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub;

use core_component;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Orchestrator for the hub cards.
 *
 * The registry is a pure function of:
 *   - which sibling plugins are installed on this site
 *   - (future) which capabilities the current user holds
 *   - the `local_lernhive_contenthub/show_ai_card` admin setting
 *
 * Plugin detection is injected as a callable so unit tests can supply
 * a fake detector and assert card state without touching the actual
 * plugin installation. The static default delegates to core_component.
 *
 * The AI card is hidden by default because no AI-backed creation
 * plugin exists in Release 1; site admins can turn it on via
 * Site administration → Plugins → Local plugins → LernHive ContentHub.
 */
class card_registry {

    /**
     * Build the ordered list of cards.
     *
     * Ordering matches the contenthub mockup: Copy → Template → Library → AI.
     * Each card is resolved independently, so missing sibling plugins do
     * not break the page — they just render as "unavailable".
     *
     * @param callable|null $detector Optional plugin detector —
     *        `fn(string $component): bool`. Defaults to a core_component
     *        lookup. Pass your own in unit tests.
     * @param bool|null $showaicard Override for the AI card toggle.
     *        `null` (default) reads the admin setting; pass an explicit
     *        boolean in tests to avoid touching config.
     * @return card[]
     */
    public static function get_cards(?callable $detector = null, ?bool $showaicard = null): array {
        $detector ??= self::default_detector();
        $showaicard ??= self::read_ai_card_setting();

        $cards = [
            self::build_copy_card($detector),
            self::build_template_card($detector),
            self::build_library_card($detector),
        ];

        if ($showaicard) {
            $cards[] = self::build_ai_card();
        }

        return $cards;
    }

    /**
     * Copy card — delegates to local_lernhive_copy when installed.
     *
     * @param callable $detector
     * @return card
     */
    private static function build_copy_card(callable $detector): card {
        $installed = (bool) $detector('local_lernhive_copy');
        $url = $installed ? new moodle_url('/local/lernhive_copy/index.php') : null;
        return new card(
            key: 'copy',
            titlestring: 'card_copy_title',
            descstring: 'card_copy_desc',
            ctastring: 'card_copy_cta',
            icon: "\u{1F4C4}", // Page facing up.
            url: $url,
            status: $installed ? card::STATUS_AVAILABLE : card::STATUS_UNAVAILABLE,
        );
    }

    /**
     * Template card — Template lives inside the copy plugin in R1
     * (see product/02-plugin-map.md: "course copy from existing courses
     * and templates"). We link to a sub-action of the copy plugin.
     *
     * @param callable $detector
     * @return card
     */
    private static function build_template_card(callable $detector): card {
        $installed = (bool) $detector('local_lernhive_copy');
        $url = $installed
            ? new moodle_url('/local/lernhive_copy/index.php', ['source' => 'template'])
            : null;
        return new card(
            key: 'template',
            titlestring: 'card_template_title',
            descstring: 'card_template_desc',
            ctastring: 'card_template_cta',
            icon: "\u{1F9E9}", // Puzzle piece.
            url: $url,
            status: $installed ? card::STATUS_AVAILABLE : card::STATUS_UNAVAILABLE,
        );
    }

    /**
     * Library card — delegates to local_lernhive_library when installed.
     *
     * @param callable $detector
     * @return card
     */
    private static function build_library_card(callable $detector): card {
        $installed = (bool) $detector('local_lernhive_library');
        $url = $installed ? new moodle_url('/local/lernhive_library/index.php') : null;
        return new card(
            key: 'library',
            titlestring: 'card_library_title',
            descstring: 'card_library_desc',
            ctastring: 'card_library_cta',
            icon: "\u{1F4DA}", // Books.
            url: $url,
            status: $installed ? card::STATUS_AVAILABLE : card::STATUS_UNAVAILABLE,
        );
    }

    /**
     * AI card — always "coming soon" in R1, hidden by default.
     *
     * @return card
     */
    private static function build_ai_card(): card {
        return new card(
            key: 'ai',
            titlestring: 'card_ai_title',
            descstring: 'card_ai_desc',
            ctastring: 'card_ai_cta',
            icon: "\u{2728}", // Sparkles.
            url: null,
            status: card::STATUS_COMING_SOON,
        );
    }

    /**
     * Default plugin detector — checks whether the given frankenstyle
     * component is installed on the site.
     *
     * @return callable (string): bool
     */
    private static function default_detector(): callable {
        return static function (string $component): bool {
            [$type, $name] = core_component::normalize_component($component);
            $dir = core_component::get_plugin_directory($type, $name);
            return $dir !== null && is_dir($dir);
        };
    }

    /**
     * Read the `show_ai_card` admin setting, defaulting to false.
     *
     * Separated into its own method so unit tests can override via the
     * `$showaicard` parameter on `get_cards()` without touching config.
     * `get_config()` returns `false` for missing settings and the
     * string `"0"`/`"1"` for checkbox values — both coerce correctly.
     *
     * @return bool
     */
    private static function read_ai_card_setting(): bool {
        return (bool) get_config('local_lernhive_contenthub', 'show_ai_card');
    }
}
