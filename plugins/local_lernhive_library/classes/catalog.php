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
 * Catalog provider.
 *
 * In R2 phase 2 this class delegates loading to dedicated source
 * implementations (manifest / remote feed). Tests may still inject
 * in-memory entries directly to keep rendering deterministic.
 */
class catalog {
    /**
     * @var catalog_entry[]
     */
    private array $entries = [];

    /**
     * @param catalog_entry[]|null $entries Optional seed list (primarily for tests).
     * @param string|null $manifestjson Optional raw JSON manifest override.
     * @param catalog_source|null $source Optional catalog source override.
     * @throws \coding_exception If a seeded entry is not a catalog_entry.
     */
    public function __construct(
        ?array $entries = null,
        ?string $manifestjson = null,
        ?catalog_source $source = null,
    ) {
        if ($entries !== null) {
            $this->entries = $this->validate_seed_entries($entries);
            return;
        }

        if ($source !== null) {
            $this->entries = $source->load_entries();
            return;
        }

        $source = $this->build_default_source($manifestjson);
        $this->entries = $source->load_entries();
    }

    /**
     * @return catalog_entry[]
     */
    public function all(): array {
        return $this->entries;
    }

    /**
     * Resolve one entry by its stable catalog id.
     *
     * @param string $id
     * @return catalog_entry|null
     */
    public function find_by_id(string $id): ?catalog_entry {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        foreach ($this->entries as $entry) {
            if ($entry->id === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function is_empty(): bool {
        return empty($this->entries);
    }

    /**
     * @param array $entries
     * @return catalog_entry[]
     * @throws \coding_exception
     */
    private function validate_seed_entries(array $entries): array {
        $validated = [];
        foreach ($entries as $index => $entry) {
            if (!($entry instanceof catalog_entry)) {
                throw new \coding_exception(
                    'Catalog seed entries must be instances of local_lernhive_library\\catalog_entry (invalid index: ' . $index . ').'
                );
            }
            $validated[] = $entry;
        }
        return $validated;
    }

    /**
     * Select the production source:
     * - explicit manifest override (tests / explicit local mode)
     * - remote managed feed when configured
     * - plugin-configured manifest fallback
     *
     * @param string|null $manifestjson
     * @return catalog_source
     */
    private function build_default_source(?string $manifestjson): catalog_source {
        $parser = new catalog_manifest_parser();

        if ($manifestjson !== null) {
            return new manifest_catalog_source($parser, $manifestjson);
        }

        $feedurl = trim((string) (get_config('local_lernhive_library', 'catalog_feed_url') ?? ''));
        if ($feedurl !== '') {
            return new remote_catalog_source($parser, $feedurl);
        }

        return new manifest_catalog_source($parser);
    }
}
