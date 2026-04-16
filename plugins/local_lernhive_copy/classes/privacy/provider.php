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
 * Privacy provider for local_lernhive_copy.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy metadata + user preference provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {

    /** @var string Preference key for stored default category id. */
    private const PREFERENCE_DEFAULT_CATEGORY = 'local_lernhive_copy_default_category';

    /**
     * Describe privacy metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            self::PREFERENCE_DEFAULT_CATEGORY,
            'privacy:metadata:preference:defaultcategory'
        );

        return $collection;
    }

    /**
     * Export user preferences.
     *
     * @param int $userid
     */
    public static function export_user_preferences(int $userid): void {
        $defaultcategory = get_user_preferences(self::PREFERENCE_DEFAULT_CATEGORY, null, $userid);
        if ($defaultcategory === null) {
            return;
        }

        writer::export_user_preference(
            'local_lernhive_copy',
            self::PREFERENCE_DEFAULT_CATEGORY,
            $defaultcategory,
            get_string('privacy:metadata:preference:defaultcategory', 'local_lernhive_copy')
        );
    }
}
