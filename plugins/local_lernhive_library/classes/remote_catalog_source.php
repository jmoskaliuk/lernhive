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
 * Remote managed-feed catalog source for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

defined('MOODLE_INTERNAL') || die();

/**
 * Load entries from a remote JSON feed.
 */
final class remote_catalog_source implements catalog_source {
    /** @var callable|null */
    private $fetcher;

    /**
     * @param catalog_manifest_parser|null $parser
     * @param string|null $feedurl Optional feed-url override.
     * @param string|null $apitoken Optional API token override.
     * @param int $timeoutseconds cURL timeout in seconds.
     * @param callable|null $fetcher Optional test fetch hook: fn(string $url, string $token): ?string
     */
    public function __construct(
        private ?catalog_manifest_parser $parser = null,
        private ?string $feedurl = null,
        private ?string $apitoken = null,
        private int $timeoutseconds = 5,
        ?callable $fetcher = null,
    ) {
        $this->parser ??= new catalog_manifest_parser();
        $this->timeoutseconds = max(1, $this->timeoutseconds);
        $this->fetcher = $fetcher;
    }

    /**
     * @return catalog_entry[]
     */
    public function load_entries(): array {
        $feedurl = trim((string) ($this->feedurl ?? get_config('local_lernhive_library', 'catalog_feed_url') ?? ''));
        if ($feedurl === '') {
            return [];
        }

        $apitoken = (string) ($this->apitoken ?? get_config('local_lernhive_library', 'catalog_feed_token') ?? '');
        $manifestjson = $this->fetch_manifest_json($feedurl, trim($apitoken));
        if ($manifestjson === null) {
            return [];
        }

        return $this->parser->parse_json($manifestjson, 'remote catalog feed');
    }

    /**
     * @param string $feedurl
     * @param string $apitoken
     * @return string|null
     */
    private function fetch_manifest_json(string $feedurl, string $apitoken): ?string {
        if ($this->fetcher !== null) {
            $manifestjson = call_user_func($this->fetcher, $feedurl, $apitoken);
            if (!is_string($manifestjson)) {
                debugging(
                    'LernHive Library remote catalog feed fetcher returned invalid data and was ignored.',
                    DEBUG_DEVELOPER
                );
                return null;
            }
            return $manifestjson;
        }

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $options = [
            'CURLOPT_TIMEOUT' => $this->timeoutseconds,
            'CURLOPT_CONNECTTIMEOUT' => min(3, $this->timeoutseconds),
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_HTTPHEADER' => $this->build_http_headers($apitoken),
        ];

        $curl = new \curl();
        $response = $curl->get($feedurl, [], $options);
        $httpinfo = $curl->get_info();
        $httpcode = (int) ($httpinfo['http_code'] ?? 0);

        if (!is_string($response) || $httpcode < 200 || $httpcode >= 300) {
            debugging(
                'LernHive Library remote catalog feed request failed (HTTP ' . $httpcode . ').',
                DEBUG_DEVELOPER
            );
            return null;
        }

        return $response;
    }

    /**
     * @param string $apitoken
     * @return array
     */
    private function build_http_headers(string $apitoken): array {
        $headers = ['Accept: application/json'];
        if ($apitoken !== '') {
            $headers[] = 'Authorization: Bearer ' . $apitoken;
        }
        return $headers;
    }
}

