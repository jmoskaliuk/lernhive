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
 * LernHive — Reset and start a user tour.
 *
 * Resets the tour completion preference so the tour plays again,
 * then redirects to the target page where the tour is configured to appear.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$tourid = required_param('tourid', PARAM_INT);

// Load the tour record to find its pathmatch (= target page).
$tour = $DB->get_record('tool_usertours_tours', ['id' => $tourid]);

if (!$tour) {
    throw new moodle_exception('invalidrecord', 'error');
}

// Reset the completion preference so the tour will play.
$prefname = 'tool_usertours_' . $tourid . '_completed';
unset_user_preference($prefname, $USER->id);

// Also reset the "last step" preference so it starts from step 1.
$steppref = 'tool_usertours_' . $tourid . '_lastStep';
unset_user_preference($steppref, $USER->id);

// Determine redirect URL from the tour's pathmatch.
// Pathmatch is like "/course/view.php%" or "/user/editadvanced.php%".
$pathmatch = $tour->pathmatch ?? '';
$redirect = $pathmatch;

// Clean up the pathmatch: remove trailing % wildcards.
$redirect = rtrim($redirect, '%');

// If pathmatch is empty or just "/", go back to tours overview.
if (empty($redirect) || $redirect === '/') {
    $redirect = new moodle_url('/local/lernhive_onboarding/tours.php');
} else {
    // Build a moodle_url. If the path contains placeholders we can't resolve,
    // just go to the tours overview.
    try {
        $redirect = new moodle_url($redirect);
    } catch (\Exception $e) {
        $redirect = new moodle_url('/local/lernhive_onboarding/tours.php');
    }
}

redirect($redirect);
