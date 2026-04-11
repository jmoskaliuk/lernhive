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
 * LernHive Onboarding — catalog-initiated tour start.
 *
 * Thin page-level wrapper: handles `require_login()`, `require_sesskey()`
 * and the final `redirect()`. Every other decision (pull `lh_start_url`
 * from `configdata`, resolve placeholders, prime `_requested=1`, clear
 * prior completion state) lives in `\local_lernhive_onboarding\starttour_flow`
 * so it can be unit-tested without driving the full request lifecycle.
 *
 * See `docs/03-dev-doc.md` → "Deterministic tour start" for the full flow.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$tourid = required_param('tourid', PARAM_INT);

$redirect = \local_lernhive_onboarding\starttour_flow::prepare_redirect_url(
    $tourid,
    (int) $USER->id
);

redirect($redirect);
