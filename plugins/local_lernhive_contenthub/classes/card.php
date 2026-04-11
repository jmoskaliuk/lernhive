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
 * ContentHub card value object.
 *
 * Represents a single content path tile shown on the hub page.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable card definition.
 *
 * A card is purely presentational data for the hub template — the card
 * itself contains no business logic. The registry decides the status
 * and target URL based on which sibling plugins are installed and the
 * user's capabilities.
 */
final class card {

    /** @var string available: click-through is enabled. */
    public const STATUS_AVAILABLE = 'available';

    /** @var string coming_soon: shown but non-interactive (grey). */
    public const STATUS_COMING_SOON = 'coming_soon';

    /** @var string unavailable: plugin missing, admin message. */
    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @param string $key          Stable identifier (copy, template, library, ai).
     * @param string $titlestring  Lang string key for the card title.
     * @param string $descstring   Lang string key for the description.
     * @param string $ctastring    Lang string key for the call-to-action label.
     * @param string $icon         Unicode or emoji glyph (no icon font needed).
     * @param moodle_url|null $url Target URL, or null for coming soon / unavailable.
     * @param string $status       One of the STATUS_* constants.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $titlestring,
        public readonly string $descstring,
        public readonly string $ctastring,
        public readonly string $icon,
        public readonly ?moodle_url $url,
        public readonly string $status,
    ) {
    }

    /**
     * Export as a flat context for the hub_page mustache template.
     *
     * The template consumes plain scalars, so we resolve lang strings
     * here rather than exposing callable sub-helpers to the view.
     *
     * @return array
     */
    public function to_template_context(): array {
        // Map card key → FontAwesome icon + colour variant for the Plugin Shell icon box.
        $icon_fa = match($this->key) {
            'copy'     => 'fa-copy',
            'template' => 'fa-file-alt',
            'library'  => 'fa-book',
            'ai'       => 'fa-magic',
            default    => 'fa-file',
        };
        $icon_color = match($this->key) {
            'copy'     => 'course',
            'template' => 'snack',
            'library'  => 'community',
            'ai'       => 'tour',
            default    => '',
        };

        return [
            'key'          => $this->key,
            'title'        => get_string($this->titlestring, 'local_lernhive_contenthub'),
            'description'  => get_string($this->descstring, 'local_lernhive_contenthub'),
            'cta'          => get_string($this->ctastring, 'local_lernhive_contenthub'),
            'icon'         => $this->icon,
            'icon_fa'      => $icon_fa,
            'icon_color'   => $icon_color,
            'url'          => $this->url ? $this->url->out(false) : '',
            'hasurl'       => $this->url !== null && $this->status === self::STATUS_AVAILABLE,
            'status'       => $this->status,
            'available'    => $this->status === self::STATUS_AVAILABLE,
            'coming_soon'  => $this->status === self::STATUS_COMING_SOON,
            'unavailable'  => $this->status === self::STATUS_UNAVAILABLE,
            'statuslabel'  => get_string('status_' . $this->status, 'local_lernhive_contenthub'),
        ];
    }
}
