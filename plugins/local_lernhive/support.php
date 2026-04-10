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
 * LernHive Support / Help page.
 *
 * Provides usage help and support information for teachers.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$PAGE->set_url(new moodle_url('/local/lernhive/support.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('support_title', 'local_lernhive'));
$PAGE->set_heading(get_string('support_title', 'local_lernhive'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
?>

<style>
.lh-support-page { max-width: 900px; margin: 0; padding: 0 0 40px 0; }
.lh-support-page h2 { font-size: 1.4rem; font-weight: 700; color: #1a1a1a; margin: 0 0 24px 0; }
.lh-support-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 32px; }
.lh-support-card {
    background: #fff; border: 1px solid #e8eaed; border-radius: 12px;
    padding: 24px; transition: box-shadow 0.2s, border-color 0.2s;
}
.lh-support-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-color: #d0d5dd; }
.lh-support-card-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px; background: rgba(58,173,170,0.10); color: #3aadaa;
}
.lh-support-card h3 { font-size: 0.95rem; font-weight: 600; color: #1a1a1a; margin: 0 0 8px 0; }
.lh-support-card p { font-size: 0.85rem; color: #5f6368; margin: 0; line-height: 1.5; }
.lh-support-info {
    background: #f8f9fa; border-radius: 12px; padding: 24px; margin-top: 24px;
    border: 1px solid #e8eaed;
}
.lh-support-info h3 { font-size: 1rem; font-weight: 600; color: #1a1a1a; margin: 0 0 12px 0; }
.lh-support-info p { font-size: 0.85rem; color: #5f6368; line-height: 1.6; margin: 0 0 8px 0; }
.lh-support-info p:last-child { margin-bottom: 0; }
</style>

<div class="lh-support-page">
    <h2><?php echo get_string('support_title', 'local_lernhive'); ?></h2>

    <div class="lh-support-grid">
        <div class="lh-support-card">
            <div class="lh-support-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
            </div>
            <h3><?php echo get_string('support_onboarding_title', 'local_lernhive'); ?></h3>
            <p><?php echo get_string('support_onboarding_desc', 'local_lernhive'); ?></p>
        </div>
        <div class="lh-support-card">
            <div class="lh-support-card-icon" style="background: rgba(25,72,102,0.10); color: #194866;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <h3><?php echo get_string('support_courses_title', 'local_lernhive'); ?></h3>
            <p><?php echo get_string('support_courses_desc', 'local_lernhive'); ?></p>
        </div>
        <div class="lh-support-card">
            <div class="lh-support-card-icon" style="background: rgba(249,128,18,0.10); color: #f98012;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h3><?php echo get_string('support_users_title', 'local_lernhive'); ?></h3>
            <p><?php echo get_string('support_users_desc', 'local_lernhive'); ?></p>
        </div>
    </div>

    <div class="lh-support-info">
        <h3><?php echo get_string('support_contact_title', 'local_lernhive'); ?></h3>
        <p><?php echo get_string('support_contact_desc', 'local_lernhive'); ?></p>
    </div>
</div>

<?php
echo $OUTPUT->footer();
