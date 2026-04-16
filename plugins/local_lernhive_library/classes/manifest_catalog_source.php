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
 * Manifest-backed catalog source for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

defined('MOODLE_INTERNAL') || die();

/**
 * Load entries from a JSON manifest.
 */
final class manifest_catalog_source implements catalog_source {
    /**
     * @param catalog_manifest_parser|null $parser
     * @param string|null $manifestjson Optional explicit JSON payload.
     */
    public function __construct(
        private ?catalog_manifest_parser $parser = null,
        private ?string $manifestjson = null,
    ) {
        $this->parser ??= new catalog_manifest_parser();
    }

    /**
     * @return catalog_entry[]
     */
    public function load_entries(): array {
        $manifestjson = $this->manifestjson;
        if ($manifestjson === null) {
            $manifestjson = (string) (get_config('local_lernhive_library', 'catalog_manifest_json') ?? '');
        }

        return $this->parser->parse_json($manifestjson, 'catalog manifest');
    }
}

