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
 * Source type for the copy wizard — course vs template.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy;

defined('MOODLE_INTERNAL') || die();

/**
 * Tiny value object that normalises the `?source=` query parameter.
 *
 * The copy plugin serves two content paths in R1:
 *   - "course"   — the default Copy card on the ContentHub
 *   - "template" — the Template card on the ContentHub, shown as a
 *                  sub-action so the wizard code can be shared
 *
 * Everything other than "template" resolves to "course" so the page
 * stays safe against stray or malicious query values.
 */
final class source {

    /** @var string Course copy mode. */
    public const TYPE_COURSE = 'course';

    /** @var string Template copy mode. */
    public const TYPE_TEMPLATE = 'template';

    /**
     * @param string $type One of the TYPE_* constants.
     */
    private function __construct(public readonly string $type) {
    }

    /**
     * Resolve a source from a raw request parameter.
     *
     * @param string|null $raw Raw value of ?source=, or null.
     * @return self
     */
    public static function from_request(?string $raw): self {
        if ($raw === self::TYPE_TEMPLATE) {
            return new self(self::TYPE_TEMPLATE);
        }
        return new self(self::TYPE_COURSE);
    }

    /**
     * @return bool
     */
    public function is_template(): bool {
        return $this->type === self::TYPE_TEMPLATE;
    }

    /**
     * Moodle lang string suffix — used to build "page_title_$suffix"
     * and "page_intro_$suffix" keys.
     *
     * @return string
     */
    public function string_suffix(): string {
        return $this->type;
    }
}
