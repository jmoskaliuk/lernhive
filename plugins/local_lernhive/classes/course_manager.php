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

namespace local_lernhive;

/**
 * Manages per-teacher course categories.
 *
 * When "Allow teachers to create courses" is enabled, this class
 * creates and manages a personal course category for each teacher,
 * assigns the coursecreator role in that category context, and
 * provides the URL for course creation.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_manager {

    /**
     * Check whether the course creation feature is enabled.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_lernhive', 'allow_teacher_course_creation');
    }

    /**
     * Ensure a personal course category exists for the given teacher.
     *
     * If the teacher already has a category (tracked in local_lernhive_teacher_cats),
     * returns the existing category ID. Otherwise creates a new category
     * under the configured parent, assigns the coursecreator role, and
     * records the mapping.
     *
     * @param int $userid The teacher's user ID.
     * @return int The course category ID.
     */
    public static function ensure_teacher_category(int $userid): int {
        global $DB;

        // Check for existing mapping.
        $existing = $DB->get_record('local_lernhive_teacher_cats', ['userid' => $userid]);
        if ($existing) {
            // Verify the category still exists.
            if ($DB->record_exists('course_categories', ['id' => $existing->categoryid])) {
                return (int) $existing->categoryid;
            }
            // Category was deleted — remove stale mapping and recreate.
            $DB->delete_records('local_lernhive_teacher_cats', ['id' => $existing->id]);
        }

        // Get teacher name for category title.
        // fullname() requires all name fields including phonetic/alternate.
        $namefields = \core_user\fields::for_name()->get_sql('', false, '', '', false)->selects;
        $user = $DB->get_record('user', ['id' => $userid], 'id, ' . $namefields, MUST_EXIST);
        $catname = fullname($user);

        // Determine parent category.
        $parentid = (int) get_config('local_lernhive', 'teacher_category_parent');

        // Create the category via Moodle API.
        $newcat = \core_course_category::create([
            'name' => $catname,
            'parent' => $parentid,
            'visible' => 1,
            'description' => get_string('teacher_category_desc', 'local_lernhive', fullname($user)),
        ]);

        // Record the mapping.
        $DB->insert_record('local_lernhive_teacher_cats', [
            'userid' => $userid,
            'categoryid' => $newcat->id,
            'timecreated' => time(),
        ]);

        // Assign coursecreator role in this category context.
        $context = \context_coursecat::instance($newcat->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        if ($roleid) {
            role_assign($roleid, $userid, $context->id);
        }

        return (int) $newcat->id;
    }

    /**
     * Get the teacher's personal category ID, if it exists.
     *
     * @param int $userid The teacher's user ID.
     * @return int|null The category ID or null.
     */
    public static function get_teacher_category(int $userid): ?int {
        global $DB;

        $record = $DB->get_record('local_lernhive_teacher_cats', ['userid' => $userid]);
        if (!$record) {
            return null;
        }

        // Verify the category still exists.
        if (!$DB->record_exists('course_categories', ['id' => $record->categoryid])) {
            return null;
        }

        return (int) $record->categoryid;
    }

    /**
     * Get the URL for creating a new course in the teacher's category.
     *
     * Ensures the category exists before building the URL.
     *
     * @param int $userid The teacher's user ID.
     * @return \moodle_url
     */
    public static function get_create_course_url(int $userid): \moodle_url {
        $categoryid = self::ensure_teacher_category($userid);
        return new \moodle_url('/course/edit.php', ['category' => $categoryid]);
    }
}
