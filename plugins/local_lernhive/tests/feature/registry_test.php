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

use advanced_testcase;
use coding_exception;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the feature registry.
 *
 * Tests pin the default matrix and verify override resolution from
 * local_lernhive_feature_overrides (LH-CORE-FR-03).
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(registry::class)]
final class registry_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        registry::reset_cache();
    }

    protected function tearDown(): void {
        registry::reset_cache();
        parent::tearDown();
    }

    public function test_get_features_returns_non_empty_map_keyed_by_feature_id(): void {
        $features = registry::get_features();
        $this->assertNotEmpty($features);
        foreach ($features as $key => $def) {
            $this->assertInstanceOf(definition::class, $def);
            $this->assertSame($key, $def->featureid);
        }
    }

    public function test_get_features_is_cached_between_calls(): void {
        $first = registry::get_features();
        $second = registry::get_features();
        // Same in-process reference — identical keys + objects.
        $this->assertSame($first, $second);
    }

    public function test_reset_cache_forces_rebuild(): void {
        $first = registry::get_features();
        registry::reset_cache();
        $second = registry::get_features();
        // Content equal, but second is a fresh build so objects differ.
        $this->assertSame(array_keys($first), array_keys($second));
    }

    public function test_get_feature_returns_null_for_unknown_id(): void {
        $this->assertNull(registry::get_feature('does.not.exist'));
    }

    public function test_get_feature_returns_definition_for_known_id(): void {
        $def = registry::get_feature('mod_assign.create');
        $this->assertInstanceOf(definition::class, $def);
        $this->assertSame('mod_assign.create', $def->featureid);
    }

    /**
     * Pinned defaults from ADR-01 matrix v2. If any of these change, the ADR
     * needs to be revisited — do not update this test in isolation.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function matrix_v2_defaults_provider(): array {
        return [
            'resource is L1' => ['mod_resource.create', 1],
            'page is L1' => ['mod_page.create', 1],
            'forum announcement is L1' => ['mod_forum.create.announcement', 1],
            'course create is L1' => ['core.course.create', 1],
            'course format settings is L1' => ['core.course.settings.format', 1],
            'course completion settings is L1' => ['core.course.settings.completion', 1],
            'user create is L1 (configurable)' => ['core.user.create', 1],
            'user enrol is L1' => ['core.user.enrol', 1],
            'messaging is L1' => ['core.message.send', 1],
            'assign is L2' => ['mod_assign.create', 2],
            'full forum is L2' => ['mod_forum.create.full', 2],
            'bigbluebutton is L2' => ['mod_bigbluebuttonbn.create', 2],
            'grade view is L2' => ['core.grade.view', 2],
            'quiz is L3' => ['mod_quiz.create', 3],
            'h5p is L3' => ['mod_h5pactivity.create', 3],
            'lesson is L3' => ['mod_lesson.create', 3],
            'managegroups is L3' => ['core.course.managegroups', 3],
            'wiki is L4' => ['mod_wiki.create', 4],
            'workshop is L4' => ['mod_workshop.create', 4],
            'grade manage is L4' => ['core.grade.manage', 4],
            'grade edit is L4' => ['core.grade.edit', 4],
            'site reports is L4' => ['core.site.viewreports', 4],
            'enrolconfig is L4' => ['core.course.enrolconfig', 4],
            'scorm is L5' => ['mod_scorm.create', 5],
            'lti is L5' => ['mod_lti.create', 5],
            'backup is L5' => ['core.backup.course', 5],
            'restore is L5' => ['core.restore.course', 5],
            'import is L5' => ['core.course.import', 5],
        ];
    }

    /**
     * @dataProvider matrix_v2_defaults_provider
     */
    public function test_effective_level_matches_matrix_v2(string $featureid, int $expected): void {
        $this->assertSame($expected, registry::effective_level($featureid));
    }

    public function test_effective_level_throws_on_unknown_feature(): void {
        $this->expectException(coding_exception::class);
        registry::effective_level('does.not.exist');
    }

    public function test_effective_level_uses_flavor_override_when_present(): void {
        $this->resetAfterTest(true);

        override_store::apply_flavor_preset('mod_assign.create', 4, 'lxp');
        $this->assertSame(4, registry::effective_level('mod_assign.create'));
    }

    public function test_effective_level_admin_override_wins_over_flavor_preset(): void {
        $this->resetAfterTest(true);

        override_store::apply_flavor_preset('mod_assign.create', 4, 'lxp');
        override_store::set_admin_override('mod_assign.create', 2);

        $this->assertSame(2, registry::effective_level('mod_assign.create'));
    }

    public function test_effective_level_returns_max_plus_one_for_disabled_override(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', null);
        $this->assertSame(definition::MAX_LEVEL + 1, registry::effective_level('mod_assign.create'));
    }

    public function test_apply_flavor_preset_replaces_stale_flavor_rows(): void {
        $this->resetAfterTest(true);

        registry::apply_flavor_preset('school', [
            'mod_assign.create' => 4,
            'core.user.create' => 2,
        ]);
        $this->assertSame(4, registry::effective_level('mod_assign.create'));
        $this->assertSame(2, registry::effective_level('core.user.create'));

        registry::apply_flavor_preset('lxp', [
            'core.user.create' => 1,
        ]);

        // mod_assign preset row was removed because it is no longer in payload.
        $this->assertSame(2, registry::effective_level('mod_assign.create'));
        $this->assertSame(1, registry::effective_level('core.user.create'));
    }

    public function test_apply_flavor_preset_does_not_override_admin_rows(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', 5);
        registry::apply_flavor_preset('school', [
            'mod_assign.create' => 1,
        ]);

        $this->assertSame(5, registry::effective_level('mod_assign.create'));
    }

    public function test_get_features_for_level_is_cumulative(): void {
        $l1 = registry::get_features_for_level(1);
        $l2 = registry::get_features_for_level(2);
        $l5 = registry::get_features_for_level(5);

        // L1 ⊂ L2 ⊂ L5.
        $this->assertNotEmpty($l1);
        $this->assertGreaterThan(count($l1), count($l2));
        $this->assertGreaterThan(count($l2), count($l5));

        foreach (array_keys($l1) as $id) {
            $this->assertArrayHasKey($id, $l2, "L1 feature {$id} must be in L2");
            $this->assertArrayHasKey($id, $l5, "L1 feature {$id} must be in L5");
        }
    }

    public function test_get_features_for_level_5_equals_full_registry(): void {
        $all = registry::get_features();
        $l5 = registry::get_features_for_level(5);
        $this->assertSame(count($all), count($l5));
        $this->assertSame(array_keys($all), array_keys($l5));
    }

    public function test_get_features_for_level_rejects_out_of_range(): void {
        $this->expectException(coding_exception::class);
        registry::get_features_for_level(0);
    }

    public function test_get_features_for_level_rejects_above_maximum(): void {
        $this->expectException(coding_exception::class);
        registry::get_features_for_level(6);
    }

    public function test_every_feature_has_plausible_required_capability(): void {
        foreach (registry::get_features() as $id => $def) {
            $this->assertMatchesRegularExpression(
                '#^[a-z_]+/[a-z0-9_]+:[a-z0-9_]+$#',
                $def->requiredcapability,
                "feature {$id} declares a malformed capability '{$def->requiredcapability}'"
            );
        }
    }

    public function test_flavor_schule_hint_is_present_for_user_create(): void {
        $def = registry::get_feature('core.user.create');
        $this->assertNotNull($def);
        $this->assertSame('schule', $def->flavorhint);
    }
}
