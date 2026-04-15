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
 * Catalog entry value object — a single item in the managed library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable description of a library item.
 *
 * Release 1 does not yet ship a real catalog source — the registry
 * returns an empty list and the catalog page renders the empty state.
 * This class defines the contract the eventual source must satisfy.
 */
final class catalog_entry {

    /**
     * @param string $id          Stable identifier supplied by the managed source.
     * @param string $title       Human-readable course title.
     * @param string $description Short teaser for the catalog view.
     * @param string $version     Semver-like version string of the .mbz.
     * @param int    $updated     Unix timestamp of the last catalog update.
     * @param string $language    ISO code of the primary course language.
     * @throws \coding_exception If required fields are blank or timestamp is invalid.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $version,
        public readonly int $updated,
        public readonly string $language,
    ) {
        $this->assert_required_string($this->id, 'id');
        $this->assert_required_string($this->title, 'title');
        $this->assert_required_string($this->version, 'version');
        $this->assert_required_string($this->language, 'language');

        if ($this->updated < 0) {
            throw new \coding_exception('Catalog entry field "updated" must be a non-negative unix timestamp.');
        }
    }

    /**
     * Export as a flat context for the catalog mustache template.
     *
     * @return array
     */
    public function to_template_context(): array {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'version'     => $this->version,
            'updated'     => userdate($this->updated, get_string('strftimedate', 'core_langconfig')),
            'language'    => strtoupper(trim($this->language)),
        ];
    }

    /**
     * Validate required string fields for the catalog contract.
     *
     * @param string $value
     * @param string $field
     * @return void
     * @throws \coding_exception
     */
    private function assert_required_string(string $value, string $field): void {
        if (trim($value) === '') {
            throw new \coding_exception('Catalog entry field "' . $field . '" must not be blank.');
        }
    }
}
