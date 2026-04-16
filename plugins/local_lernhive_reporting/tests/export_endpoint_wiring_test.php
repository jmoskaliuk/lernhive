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
 * Unit tests for export endpoint wiring and security guards.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversNothing
 */
final class export_endpoint_wiring_test extends advanced_testcase {

    /**
     * Endpoint scripts keep export wiring guarded by capability + sesskey.
     *
     * @param string $script Relative script path in plugin root.
     * @param string $downloadcall Expected export service call expression.
     * @dataProvider endpoint_script_provider
     */
    public function test_export_endpoint_wiring_is_capability_and_sesskey_guarded(
        string $script,
        string $downloadcall
    ): void {
        $this->resetAfterTest();

        $source = $this->read_plugin_file($script);

        $this->assertStringContainsString('use local_lernhive_reporting\\export_service;', $source);
        $this->assertStringContainsString("\$export = optional_param('export', '', PARAM_ALPHA);", $source);
        $this->assertStringContainsString("require_capability('local/lernhive_reporting:view', \$context);", $source);
        $this->assertStringContainsString("if (\$export === 'csv') {", $source);
        $this->assertStringContainsString('require_sesskey();', $source);
        $this->assertStringContainsString('(new export_service())->' . $downloadcall, $source);

        $capabilitypos = strpos($source, "require_capability('local/lernhive_reporting:view', \$context);");
        $exportpos = strpos($source, "if (\$export === 'csv') {");

        $this->assertNotFalse($capabilitypos);
        $this->assertNotFalse($exportpos);
        $this->assertGreaterThan($capabilitypos, $exportpos);
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function endpoint_script_provider(): array {
        return [
            'users' => ['users.php', 'download_users_csv($courseid);'],
            'popular' => ['popular.php', 'download_popular_csv();'],
            'completion' => ['completion.php', 'download_completion_csv();'],
        ];
    }

    /**
     * Read plugin-local file from tests directory.
     *
     * @param string $relativepath
     * @return string
     */
    private function read_plugin_file(string $relativepath): string {
        $path = __DIR__ . '/../' . ltrim($relativepath, '/');
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->fail('Unable to read test target: ' . $path);
        }
        return $contents;
    }
}
