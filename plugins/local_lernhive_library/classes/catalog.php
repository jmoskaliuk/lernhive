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
 * Catalog source for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

defined('MOODLE_INTERNAL') || die();

/**
 * Placeholder catalog provider.
 *
 * Release 1 does not yet connect to eLeDia's managed catalog backend;
 * this class returns an empty list so the page renders the empty
 * state. Tests can inject fake entries via the constructor, which
 * keeps `catalog_page` testable without touching the network.
 */
class catalog {

    /**
     * @param catalog_entry[] $entries Optional seed list (for tests).
     */
    public function __construct(private readonly array $entries = []) {
    }

    /**
     * @return catalog_entry[]
     */
    public function all(): array {
        return $this->entries;
    }

    /**
     * @return bool
     */
    public function is_empty(): bool {
        return empty($this->entries);
    }
}
