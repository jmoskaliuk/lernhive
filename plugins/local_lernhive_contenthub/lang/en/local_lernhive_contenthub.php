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
 * English strings for local_lernhive_contenthub.
 *
 * String policy (see AGENTS.md "String rules"):
 *   - reuse Moodle core strings when semantically correct
 *   - only add plugin-specific strings for LernHive product terms
 *     (ContentHub, Snack, Template, Library, ...)
 *   - the plugin's labels are intentionally English-first
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin identity.
$string['pluginname'] = 'LernHive ContentHub';

// Capabilities (db/access.php).
$string['lernhive_contenthub:view'] = 'View the LernHive ContentHub entry page';

// Admin tree.
$string['open_hub'] = 'Open ContentHub';
$string['settings_title'] = 'ContentHub settings';
$string['setting_show_ai_card'] = 'Show AI card';
$string['setting_show_ai_card_desc'] = 'Show the AI draft card on the ContentHub entry page. The card is always rendered as "Coming soon" in Release 1 — enable it to preview the R2 direction or disable it to keep the hub focused on the three shipping paths.';

// Page.
$string['page_title'] = 'ContentHub';
$string['page_heading'] = 'How would you like to start?';
$string['page_intro'] = 'Pick one of the content paths below. ContentHub only guides you to the right starting point — the actual work happens in the matching LernHive plugin.';

// Card: Copy.
$string['card_copy_title'] = 'Copy';
$string['card_copy_desc'] = 'Reuse an existing course from your site as a starting point.';
$string['card_copy_cta'] = 'Copy a course';

// Card: Template.
$string['card_template_title'] = 'Template';
$string['card_template_desc'] = 'Start from a predefined course structure curated for your flavour.';
$string['card_template_cta'] = 'Use a template';

// Card: Library.
$string['card_library_title'] = 'Library';
$string['card_library_desc'] = 'Import a ready-to-use course from the managed LernHive library.';
$string['card_library_cta'] = 'Open the library';

// Card: AI.
$string['card_ai_title'] = 'AI draft';
$string['card_ai_desc'] = 'Generate a first draft with AI assistance. This path arrives in a later release.';
$string['card_ai_cta'] = 'Preview AI path';

// Card status.
$string['status_available'] = 'Available';
$string['status_coming_soon'] = 'Coming soon';
$string['status_unavailable'] = 'Unavailable — plugin not installed';

// Errors & access.
$string['err_no_access'] = 'You are not allowed to use ContentHub.';

// Plugin Shell strings (0.1.2).
$string['shell_tagline']         = 'Content Creation';
$string['shell_subtitle']        = 'Choose a starting point for new course content.';
$string['backtodashboard']       = 'Dashboard';
$string['tag_content_creation']  = 'Content Creation';
$string['tag_paths_available']   = 'paths available';
$string['infobar_hint']          = 'ContentHub guides you to the right plugin — all actual work happens there.';
$string['card_info']             = 'Path details';

// Privacy.
$string['privacy:metadata'] = 'The LernHive ContentHub plugin does not store any personal data. It only renders a unified entry screen and delegates all actions to sibling plugins.';
