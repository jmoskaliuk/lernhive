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

use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Hook callbacks for LernHive.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Inject LernHive level indicator into the page.
     *
     * This hook callback injects a small banner at the top of course pages
     * showing the teacher's current LernHive level and a hint about
     * locked features.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $USER, $PAGE;

        // --- Login Page Branding (before auth check) ---
        if ($PAGE->pagelayout === 'login') {
            self::inject_login_branding($hook);
            return;
        }

        // Only show for logged-in, non-guest users.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Get the user's level record — if none exists, no LernHive features apply.
        $record = level_manager::get_level_record($USER->id);
        if (!$record) {
            return;
        }

        $level = level_manager::get_level($USER->id);

        // --- Dashboard Content (Welcome Banner, Stats, Tour-Tiles, Buttons) ---
        if ($PAGE->pagetype === 'my-index' && ($PAGE->theme->name ?? '') !== 'lernhive') {
            self::inject_dashboard_content($hook, $level);
        }

        // --- User Creation/Edit Form Simplification (Explorer level) ---
        if ($level <= 1 && (strpos($PAGE->pagetype, 'admin-user-editadvanced') === 0
            || $PAGE->pagetype === 'user-editadvanced')) {
            self::inject_user_form_filter($hook, $level);
        }

        // --- Profile Dropdown + Navbar Search (all pages, Explorer level) ---
        if ($level <= 1) {
            self::inject_usermenu_filter($hook);
            self::inject_navbar_search($hook);
        }

        // --- Course page: navbar icons + sidebar with course index ---
        if ($level <= 1 && strpos($PAGE->pagetype, 'course-view') === 0) {
            self::inject_course_navbar_icons($hook);
            self::inject_course_view_redesign($hook, $level);
        }

        // --- Profile Page Redesign (Explorer level) ---
        if ($PAGE->pagetype === 'user-profile' && $level <= 1) {
            self::inject_profile_redesign($hook, $level);
        }

        // --- Course Index Simplification (Explorer level) ---
        if ($PAGE->pagetype === 'course-index' && $level <= 1) {
            self::inject_course_index_filter($hook);
        }

        // --- Course Participants → LernHive pages (Explorer level) ---
        // Redirect /user/index.php to the LernHive enrol page (course context)
        // or to /lernhive/users.php (no course context).
        if ($PAGE->pagetype === 'user-index' && $level <= 1) {
            $courseid = optional_param('id', 0, PARAM_INT);
            if ($courseid > 1) {
                $redirecturl = (new \moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]))->out(true);
            } else {
                $redirecturl = (new \moodle_url('/local/lernhive/users.php'))->out(true);
            }
            $hook->add_html('<script>window.location.replace("' . $redirecturl . '");</script>');
            return; // Don't process anything else.
        }

        // Only act on course-related pages for the remaining filters.
        // Note: Participants page is user-index (not course-view-participants).
        $validpagetypes = ['course-view', 'course-edit', 'mod-'];
        $oncourserelatedpage = false;
        foreach ($validpagetypes as $type) {
            if (strpos($PAGE->pagetype, $type) === 0) {
                $oncourserelatedpage = true;
                break;
            }
        }

        // --- Sidebar on non-dashboard, non-course, non-admin pages (Explorer level) ---
        // Course view/activity pages use Moodle's own course navigation instead.
        // Admin pages (/admin/*) use Moodle's admin navigation tree — don't override.
        // Exceptions: course-edit (creation) and user-edit pages DO get the sidebar.
        $onadminpage = (strpos($PAGE->pagetype, 'admin-') === 0)
            || ($PAGE->pagelayout === 'admin');
        $isuseredit = (strpos($PAGE->pagetype, 'admin-user-editadvanced') === 0)
            || ($PAGE->pagetype === 'user-editadvanced');
        $isuserpage = (strpos($PAGE->pagetype, 'user-') === 0);
        $needssidebar = $level <= 1
            && $PAGE->pagetype !== 'my-index'
            && (!$onadminpage || $isuseredit || $isuserpage)
            && (!$oncourserelatedpage || $PAGE->pagetype === 'course-edit');
        if ($needssidebar) {
            self::inject_sidebar_global($hook, $level);
        }

        if (!$oncourserelatedpage) {
            return;
        }

        // --- UI Simplification (always active, independent of level bar) ---

        // Course Navigation Filter (on ALL course pages — hide Grades, Activities, More).
        self::inject_course_navigation_filter($hook, $level);

        // Course Settings Filter (for course-edit pages).
        if ($PAGE->pagetype === 'course-edit') {
            self::inject_course_settings_filter($hook, $level);
            if ($level <= 1) {
                self::inject_course_edit_heading($hook, $level);
            }
        }

        // Participants Page Filter.
        if ($PAGE->pagetype === 'course-view-participants') {
            self::inject_participants_filter($hook, $level);
        }

        // Activity Chooser Filter + init (on course-view pages).
        if (strpos($PAGE->pagetype, 'course-view') === 0) {
            self::inject_activity_chooser_init($hook);
            self::inject_activity_chooser_filter($hook, $level);
        }

        // Activity Form Simplification (on mod edit pages — hide advanced sections).
        if (strpos($PAGE->pagetype, 'mod-') === 0 && substr($PAGE->pagetype, -4) === '-mod') {
            self::inject_activity_form_filter($hook, $level);
        }

        // Filepicker Filter (on all pages that may have file uploads).
        self::inject_filepicker_filter($hook, $level);

        // --- Level Bar (optional, controlled by admin setting) ---
        if (!get_config('local_lernhive', 'show_levelbar')) {
            return;
        }

        $levelname = level_manager::get_level_name($level);
        $icons = [
            1 => "\xF0\x9F\x8C\xB1",  // 🌱
            2 => "\xE2\x9C\x8F\xEF\xB8\x8F",  // ✏️
            3 => "\xF0\x9F\x8E\xAF",  // 🎯
            4 => "\xF0\x9F\x9A\x80",  // 🚀
            5 => "\xF0\x9F\x91\x91",  // 👑
        ];
        $icon = $icons[$level] ?? '';

        $nextlevelinfo = '';
        if ($level < level_manager::LEVEL_MAX) {
            $nextlevel = $level + 1;
            $nextlevelname = level_manager::get_level_name($nextlevel);
            $locked = capability_mapper::get_locked_modules($level);
            $nextmodules = array_filter($locked, fn($m) => $m['unlocks_at'] === $nextlevel);

            if (!empty($nextmodules)) {
                $modnames = [];
                foreach (array_slice($nextmodules, 0, 3) as $m) {
                    $modname = str_replace('mod_', '', $m['module']);
                    $strmanager = get_string_manager();
                    if ($strmanager->string_exists('pluginname', $modname)) {
                        $modnames[] = get_string('pluginname', $modname);
                    } else {
                        $modnames[] = ucfirst($modname);
                    }
                }

                $nextlevelinfo = '<span class="lernhive-next">' .
                    get_string('next_level_hint', 'local_lernhive', [
                        'levelname' => $nextlevelname,
                        'modules' => implode(', ', $modnames),
                    ]) . '</span>';
            }
        }

        $html = '<div id="lernhive-level-bar" class="lernhive-level-bar lernhive-level-' . $level . '">';
        $html .= '<span class="lernhive-level-info">';
        $html .= $icon . ' <strong>LernHive</strong>: ';
        $html .= get_string('current_level', 'local_lernhive', [
            'level' => $level,
            'name' => $levelname,
        ]);
        $html .= '</span>';
        $html .= $nextlevelinfo;
        // Onboarding link (requires local_lernhive_start).
        $toursurl = new \moodle_url('/local/lernhive_start/tours.php');
        $html .= '<a href="' . $toursurl->out() . '" class="lernhive-lernpfad-link">';
        $html .= '<i class="fa fa-rocket fa-fw"></i> ' . get_string('onboarding_nav_link', 'local_lernhive');
        $html .= '</a>';
        $html .= '</div>';

        $hook->add_html($html);
    }

    /**
     * Inject CSS + JS to simplify the secondary course navigation.
     *
     * Level 1 (Explorer):
     * - Hide: Grades, Activities/Reports, and everything under "More" dropdown
     * - Keep: Course (home) + Participants
     * - Add: Course Settings button (direct link to edit page)
     * - Style remaining nav items as clear buttons
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_course_navigation_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        $html = <<<'HTML'
<style>
/* LernHive Explorer: hide block drawer (right sidebar) */
.drawer.drawer-right,
#theme_boost-drawers-blocks,
[data-region="blocks-column"],
button[data-toggler="drawers"][title*="block" i],
button[data-toggler="drawers"][aria-controls*="blocks"] {
    display: none !important;
}
/* Remove drawer space reservation and left-align content */
.main-inner,
#topofscroll {
    margin-right: 0 !important;
    margin-left: 0 !important;
}
/* Reduce excessive top spacing on all course-related pages */
#topofscroll {
    margin-top: 8px !important;
    padding-top: 8px !important;
}
/* Left-align page heading and navigation on ALL course-related pages */
.page-header-headings,
.page-header-headings h1,
.page-header-headings h2,
#page-header .page-header-headings,
#page-header .page-header-headings h1,
#page-header h1,
#region-main h2:first-of-type {
    text-align: left !important;
}
.secondary-navigation .moremenu.navigation,
.secondary-navigation ul.nav,
.secondary-navigation ul.more-nav {
    justify-content: flex-start !important;
    margin-left: 0 !important;
}
/* Left-align content inside #region-main (activity titles etc.) */
#region-main .page-header-headings,
#region-main h2:first-of-type,
#region-main > .box > h2,
.activity-header .page-header-headings,
.activity-header h2 {
    text-align: left !important;
}
#region-main {
    max-width: 100% !important;
    margin-left: 0 !important;
}
/* Left-align breadcrumb on activity pages */
.breadcrumb-wrapper,
nav[aria-label="Navigation bar"],
nav[aria-label="Navigationspfad"] {
    justify-content: flex-start !important;
    text-align: left !important;
}
/* Left-align page header container (prevents centering from max-width + auto margin) */
#page-header {
    margin-left: 0 !important;
    margin-right: auto !important;
    text-align: left !important;
}
#page-header .w-100,
#page-header .d-flex {
    justify-content: flex-start !important;
}
/* LernHive Explorer: hide original "Collapse all / Expand all" text link */
a.section-collapsemenu {
    display: none !important;
}

/* LernHive Explorer: hide section action menus (three-dot) completely */
.section-header .section-actions,
.course-section-header .action-menu,
.section_action_menu,
[data-region="section-actions"],
.course-section .section-header .actions {
    display: none !important;
}
/* LernHive Explorer: ensure section-level Add Activity buttons are visible in edit mode.
   Only target the last divider per section (the "Insert content in section" button),
   NOT the between-activity dividers (which use hover-reveal by default). */
.editing .course-section > .divider:last-of-type .btn.add-content,
.editing .course-content > ul > li > .divider:last-of-type .btn.add-content {
    visibility: visible !important;
    opacity: 1 !important;
}

/* LernHive Explorer: replace course index Expand/Collapse dropdown with toggle icon */
#courseindexdrawercontrolsmenubutton,
#courseindexdrawercontrolsmenubutton + .dropdown-menu {
    display: none !important;
}
.lernhive-expand-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    background: #fff;
    color: #666;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
    flex-shrink: 0;
}
.lernhive-expand-toggle:hover {
    border-color: #0f6cbf;
    color: #0f6cbf;
    background: #f0f7ff;
}
.lernhive-expand-toggle svg {
    width: 18px;
    height: 18px;
    transition: transform 0.25s ease;
}
.lernhive-expand-toggle.expanded svg {
    transform: rotate(180deg);
}

/* LernHive Explorer: style kept nav items as buttons */
.lernhive-nav-btn {
    display: inline-block !important;
    padding: 6px 16px !important;
    margin: 2px 4px !important;
    border: 1px solid #0f6cbf !important;
    border-radius: 4px !important;
    background: #fff !important;
    color: #0f6cbf !important;
    text-decoration: none !important;
    font-weight: 500 !important;
    font-size: 0.9rem !important;
    cursor: pointer !important;
    transition: background 0.15s, color 0.15s !important;
}
.lernhive-nav-btn:hover {
    background: #0f6cbf !important;
    color: #fff !important;
    text-decoration: none !important;
}
.lernhive-nav-btn.active {
    background: #0f6cbf !important;
    color: #fff !important;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Find the secondary navigation (course tabs).
    var secNav = document.querySelector('.secondary-navigation');
    if (!secNav) return;

    // Allowed nav items (by link text OR href pattern, case-insensitive).
    var allowedByText = ["course", "kurs"];
    var allowedByHref = ["/user/index.php", "/course/edit.php", "/enrol/instances.php"];

    // Find all nav links/items.
    var navList = secNav.querySelector('ul');
    if (!navList) return;

    var navItems = navList.querySelectorAll(':scope > li');
    var courseId = null;

    // Extract course ID from current URL.
    var m = window.location.href.match(/[\?&]id=(\d+)/);
    if (m) courseId = m[1];

    // --- Replace course index Expand/Collapse dropdown with toggle icon ---
    (function() {
        // The button has id="courseindexdrawercontrolsmenubutton" inside a .dropdown wrapper.
        var origBtn = document.getElementById("courseindexdrawercontrolsmenubutton");
        if (!origBtn) return;

        var dropdownWrapper = origBtn.closest(".dropdown");
        if (!dropdownWrapper) return;

        // Find the menu items inside the dropdown.
        var expandItem = null;
        var collapseItem = null;
        dropdownWrapper.querySelectorAll(".dropdown-item").forEach(function(item) {
            var txt = (item.textContent || "").trim().toLowerCase();
            if (txt.indexOf("expand") > -1 || txt.indexOf("aufklappen") > -1) expandItem = item;
            if (txt.indexOf("collapse") > -1 || txt.indexOf("zuklappen") > -1) collapseItem = item;
        });

        // Hide entire dropdown wrapper.
        dropdownWrapper.style.display = "none";

        // Create new toggle button.
        var toggleBtn = document.createElement("button");
        toggleBtn.className = "lernhive-expand-toggle";
        toggleBtn.title = "Alle öffnen";
        toggleBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

        var isExpanded = false;
        toggleBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!isExpanded) {
                if (expandItem) expandItem.click();
                toggleBtn.classList.add("expanded");
                toggleBtn.title = "Alle schließen";
                isExpanded = true;
            } else {
                if (collapseItem) collapseItem.click();
                toggleBtn.classList.remove("expanded");
                toggleBtn.title = "Alle öffnen";
                isExpanded = false;
            }
        });

        // Insert next to the hidden dropdown.
        dropdownWrapper.parentNode.insertBefore(toggleBtn, dropdownWrapper);
    })();

    // --- Replace "Collapse all / Expand all" link in course content area ---
    (function() {
        // Moodle uses <a class="section-collapsemenu"> with child spans
        // <span class="collapseall"> and <span class="expandall">.
        var collapseLink = document.querySelector("a.section-collapsemenu");
        if (!collapseLink) return;

        // Create replacement icon button.
        var iconBtn = document.createElement("button");
        iconBtn.className = "lernhive-expand-toggle";
        iconBtn.type = "button";

        // Detect initial state: if .collapseall span is visible, sections are expanded.
        var collapseSpan = collapseLink.querySelector(".collapseall");
        var startExpanded = collapseSpan && window.getComputedStyle(collapseSpan).display !== "none";
        if (startExpanded) {
            iconBtn.classList.add("expanded");
            iconBtn.title = "Alle schließen";
        } else {
            iconBtn.title = "Alle öffnen";
        }

        iconBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

        iconBtn.addEventListener("click", function(e) {
            e.preventDefault();
            // Trigger the original Moodle link.
            collapseLink.click();
            // Toggle visual state.
            iconBtn.classList.toggle("expanded");
            iconBtn.title = iconBtn.classList.contains("expanded") ? "Alle schließen" : "Alle öffnen";
        });

        // Replace original link with our icon.
        collapseLink.style.display = "none";
        collapseLink.parentNode.insertBefore(iconBtn, collapseLink);
    })();

    // --- Activity action menus: keep only Hide and Duplicate ---
    var activityMenuObserver = new MutationObserver(function() {
        // Target the dropdown menus that appear for course module actions.
        document.querySelectorAll('.activity-actions .dropdown-menu, .action-menu .dropdown-menu').forEach(function(menu) {
            if (menu.dataset.lernhiveFiltered) return;
            menu.dataset.lernhiveFiltered = "1";
            menu.querySelectorAll('.dropdown-item, a[role="menuitem"], [role="menuitem"]').forEach(function(item) {
                var text = (item.textContent || "").trim().toLowerCase();
                // Keep only Hide/Show and Duplicate.
                var keep = (text.indexOf("hide") > -1 || text.indexOf("verbergen") > -1 ||
                            text.indexOf("show") > -1 || text.indexOf("anzeigen") > -1 ||
                            text.indexOf("duplicate") > -1 || text.indexOf("duplizieren") > -1);
                if (!keep) {
                    item.style.display = "none";
                }
            });
            // Also hide any dividers that are now between two hidden items.
            menu.querySelectorAll('.dropdown-divider, [role="separator"]').forEach(function(div) {
                div.style.display = "none";
            });
        });
    });
    activityMenuObserver.observe(document.body, { childList: true, subtree: true });

    navItems.forEach(function(item) {
        var link = item.querySelector('a');
        if (!link) { item.style.display = "none"; return; }

        var text = link.textContent.trim().toLowerCase();
        var href = (link.getAttribute("href") || "").toLowerCase();

        // Check if allowed by text match (Course/Kurs).
        var textAllowed = allowedByText.some(function(a) { return text === a; });
        // Check if allowed by href match (Participants, Settings).
        var hrefAllowed = allowedByHref.some(function(h) { return href.indexOf(h) > -1; });

        if (textAllowed || hrefAllowed) {
            // Style as button.
            link.classList.add("lernhive-nav-btn");
            // Mark active page.
            if (link.getAttribute("aria-current") || item.classList.contains("active")) {
                link.classList.add("active");
            }
            // Remove default nav styling.
            link.classList.remove("nav-link");
            item.style.listStyle = "none";
        } else if (item.classList.contains("dropdownmoremenu")) {
            // Hide "More" dropdown entirely.
            item.style.display = "none";
        } else {
            // Hide non-allowed items (Grades, Activities, etc.).
            item.style.display = "none";
        }
    });
});
</script>
HTML;

        $hook->add_html($html);
    }

    /**
     * Inject CSS + JavaScript to simplify the course settings form based on level.
     *
     * CSS is injected first (no flicker), JS handles only dynamic logic
     * (setting values, auto-slugify shortname).
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_course_settings_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        // CSS: immediate hide — no flicker.
        $css = <<<'CSS'
<style>
/* LernHive Explorer: Course Settings — hide fields & sections instantly */
#fitem_id_shortname,
#fitem_id_category,
#fitem_id_visible,
#fitem_id_idnumber,
#id_courseformathdr,
#id_appearancehdr,
#id_filehdr,
#id_groups,
#id_tagshdr,
#id_completionhdr,
#id_startdate_hour,
#id_startdate_minute,
#id_enddate_hour,
#id_enddate_minute {
    display: none !important;
}
</style>
CSS;

        // JS: only for dynamic logic (set values, slugify).
        $js = <<<'JS'
<script>
document.addEventListener("DOMContentLoaded", function() {
    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val;
    }
    function slugify(str) {
        return str.toLowerCase()
            .replace(/[äÄ]/g,"ae").replace(/[öÖ]/g,"oe")
            .replace(/[üÜ]/g,"ue").replace(/[ß]/g,"ss")
            .replace(/[^a-z0-9\s-]/g,"").replace(/[\s]+/g,"-")
            .replace(/-+/g,"-").replace(/^-|-$/g,"").substring(0,100);
    }

    // Set defaults for hidden fields.
    setVal("id_visible", "1");
    setVal("id_enablecompletion", "1");
    setVal("id_showcompletionconditions", "1");

    // Auto-generate shortname from fullname.
    var fn = document.getElementById("id_fullname");
    var sn = document.getElementById("id_shortname");
    if (fn && sn) {
        fn.addEventListener("input", function() {
            sn.value = slugify(fn.value);
        });
        var form = fn.closest("form");
        if (form) {
            form.addEventListener("submit", function() {
                if (!sn.value.trim()) sn.value = slugify(fn.value);
            });
        }
    }
});
</script>
JS;

        $hook->add_html($css . $js);
    }

    /**
     * Inject CSS + JavaScript to simplify the participants page based on level.
     *
     * CSS hides elements with reliable selectors instantly (no flicker).
     * JS handles everything that needs DOM traversal or text matching.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_participants_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        // CSS: instant hide for elements with reliable selectors.
        $css = <<<'CSS'
<style>
/* LernHive Explorer: Participants — instant hide */
#action_bar,
.initialbar,
[data-region="participant-count"],
.filter-group,
div[id^="core-filter-"],
label[for="formactionid"],
#formactionid {
    display: none !important;
}
/* Hide the "With selected users" wrapper row (contains only label + select) */
#formactionid:only-of-type ~ label,
.d-flex:has(#formactionid) {
    display: none !important;
}
/* Left-align headings on Participants page */
#page-header {
    margin-left: 0 !important;
    margin-right: auto !important;
    text-align: left !important;
}
#page-header .w-100,
#page-header .d-flex {
    justify-content: flex-start !important;
}
/* Status badges */
.lernhive-status-active {
    font-size: 0.72rem;
    background: #d4f0ef;
    color: #3aadaa;
    padding: 2px 10px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-block;
}
.lernhive-status-suspended {
    font-size: 0.72rem;
    background: #fdf0e0;
    color: #e67e22;
    padding: 2px 10px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-block;
}
/* Action icons in participants table */
.lernhive-participant-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}
.lernhive-participant-actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    color: #6c757d;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.lernhive-participant-actions a:hover { background: #e9ecef; color: #1a1a1a; }
.lernhive-participant-actions a.lh-act-info:hover { color: #65a1b3; background: #dfedf1; }
.lernhive-participant-actions a.lh-act-edit:hover { color: #194866; background: #e0eaf2; }
.lernhive-participant-actions a.lh-act-delete:hover { color: #ab1d79; background: #f5e0ed; }
</style>
CSS;

        // JS: dynamic elements — uses MutationObserver for AJAX-reloaded content.
        $js = <<<'JS'
<script>
document.addEventListener("DOMContentLoaded", function() {

    // --- Hide the filter form ---
    document.querySelectorAll(".filter-group, div[id^='core-filter-']").forEach(function(fg) {
        fg.style.display = "none";
    });

    // --- Hide "With selected users..." select + label ---
    var formaction = document.getElementById("formactionid");
    if (formaction) {
        formaction.style.display = "none";
        var wrapper = formaction.parentElement;
        if (wrapper && !wrapper.querySelector('#participants')) {
            wrapper.style.display = "none";
        }
    }
    var fLabel = document.querySelector('label[for="formactionid"]');
    if (fLabel) fLabel.style.display = "none";

    // --- Table: hide columns — reusable function for AJAX pagination ---
    function hideParticipantColumns() {
        var table = document.getElementById("participants");
        if (!table) return;

        var headerRow = table.querySelector("thead tr");
        if (!headerRow) return;

        var hideColIndices = [];
        var headerCells = headerRow.cells;

        for (var i = 0; i < headerCells.length; i++) {
            var th = headerCells[i];
            var text = th.textContent.trim();
            var shouldHide = false;
            // Checkbox column.
            if (th.querySelector(".form-check, input[type=checkbox]") || text === "Select all" || text === "Auswahl") shouldHide = true;
            // Groups column.
            if (text === "Groups" || text === "Gruppen") shouldHide = true;

            if (shouldHide) {
                th.style.display = "none";
                hideColIndices.push(i);
            }
        }

        if (hideColIndices.length > 0) {
            table.querySelectorAll("tbody tr").forEach(function(row) {
                var cells = row.cells;
                hideColIndices.forEach(function(idx) {
                    if (cells[idx]) cells[idx].style.display = "none";
                });
            });
        }
    }

    // Run immediately.
    hideParticipantColumns();

    // Re-run when table is reloaded (AJAX pagination, sorting, etc.).
    var tableObserver = new MutationObserver(function() {
        hideParticipantColumns();
    });
    var tableContainer = document.getElementById("participants") || document.getElementById("region-main");
    if (tableContainer) {
        tableObserver.observe(tableContainer.parentElement || tableContainer, {
            childList: true, subtree: true
        });
    }

    // --- Enrollment modal filter: hide fields for Explorer level ---
    function filterEnrolmentModal() {
        // Hide "Enrolment method" row.
        var methodRow = document.getElementById("fitem_id_enrolmentmethod");
        if (methodRow) methodRow.style.display = "none";

        // Hide "Enrolment created" row.
        var createdRow = document.getElementById("fitem_id_enrolmentcreated");
        if (createdRow) createdRow.style.display = "none";

        // Hide "Recover user's old grades if possible" row.
        var recoverRow = document.getElementById("fitem_id_recovergrades");
        if (recoverRow) recoverRow.style.display = "none";

        // Hide "Enrolment duration" row.
        var durationRow = document.getElementById("fitem_id_duration");
        if (durationRow) durationRow.style.display = "none";

        // Auto-select "Student" role in the role select.
        var roleSelect = document.getElementById("id_roleid");
        if (roleSelect) {
            // Find the Student/Teilnehmer option and select it.
            for (var i = 0; i < roleSelect.options.length; i++) {
                var optText = roleSelect.options[i].text.toLowerCase();
                if (optText.indexOf("student") >= 0 || optText.indexOf("teilnehmer") >= 0) {
                    roleSelect.selectedIndex = i;
                    break;
                }
            }
            // Hide the role row since it's auto-selected.
            var roleRow = roleSelect.closest(".fitem") || document.getElementById("fitem_id_roleid");
            if (roleRow) roleRow.style.display = "none";
        }

        // Hide Hour + Minute selects in timestart and timeend.
        ["timestart", "timeend"].forEach(function(field) {
            var row = document.getElementById("fitem_id_" + field);
            if (!row) return;
            row.querySelectorAll("select").forEach(function(sel) {
                var name = sel.name || sel.id || "";
                if (name.match(/hour/i) || name.match(/minute/i)) {
                    sel.style.display = "none";
                    var parent = sel.parentElement;
                    if (parent && parent.querySelectorAll("select").length === 1) {
                        parent.style.display = "none";
                    }
                }
            });
        });

        // Also hide in generic label/field patterns (various Moodle versions).
        document.querySelectorAll(".fitem").forEach(function(fitem) {
            var labels = fitem.querySelectorAll("label");
            labels.forEach(function(lbl) {
                var text = lbl.textContent.trim().toLowerCase();
                if (text.match(/recover.*grade/) || text.match(/alte bewertung/) ||
                    text.match(/enrolment duration/) || text.match(/einschreibedauer/)) {
                    fitem.style.display = "none";
                }
            });
        });

        // Also hide enrolment method in dt/dd pairs.
        document.querySelectorAll("dt").forEach(function(dt) {
            var text = dt.textContent.trim().toLowerCase();
            if (text.match(/enrolment method/) || text.match(/einschreibemethode/) ||
                text.match(/enrollment method/)) {
                dt.style.display = "none";
                var dd = dt.nextElementSibling;
                if (dd && dd.tagName === "DD") dd.style.display = "none";
            }
        });
        document.querySelectorAll("th, td, label, .form-group").forEach(function(el) {
            var text = el.textContent.trim().toLowerCase();
            if ((text === "enrolment method" || text === "einschreibemethode" || text === "enrollment method") && el.children.length <= 1) {
                var row = el.closest("tr") || el.closest(".form-group") || el.closest(".fitem");
                if (row) row.style.display = "none";
            }
        });
    }

    // Run on modal open (MutationObserver).
    var modalObserver = new MutationObserver(function() {
        filterEnrolmentModal();
    });
    modalObserver.observe(document.body, { childList: true, subtree: true });

    // --- Hide "X participants found" text ---
    document.querySelectorAll("#region-main p, #region-main span, #region-main div").forEach(function(el) {
        if (el.children.length === 0 && el.textContent.match(/\d+\s+participant/i)) {
            el.style.display = "none";
        }
    });

    // --- Status badges + Action icons (Info, Edit, Delete) ---
    function addStatusBadgesAndActions() {
        var table = document.getElementById("participants");
        if (!table) return;

        var svgInfo = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
        var svgEdit = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
        var svgDelete = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

        // Add actions header if not yet present.
        var thead = table.querySelector("thead tr");
        if (thead && !thead.querySelector(".lh-actions-th")) {
            var th = document.createElement("th");
            th.className = "lh-actions-th";
            th.textContent = "";
            th.style.width = "120px";
            thead.appendChild(th);
        }

        table.querySelectorAll("tbody tr").forEach(function(row) {
            if (row.querySelector(".lernhive-participant-actions")) return; // already processed

            var profileLink = row.querySelector('a[href*="/user/view.php"], a[href*="/user/profile.php"]');
            if (!profileLink) return;
            var href = profileLink.getAttribute("href");
            var uidMatch = href.match(/[?&]id=(\d+)/);
            if (!uidMatch) return;
            var uid = uidMatch[1];

            // Style status cells with badges.
            row.querySelectorAll("td").forEach(function(td) {
                var txt = td.textContent.trim();
                if (txt === "Active" || txt === "Aktiv") {
                    td.innerHTML = '<span class="lernhive-status-active">Aktiv</span>';
                } else if (txt === "Suspended" || txt === "Gesperrt") {
                    td.innerHTML = '<span class="lernhive-status-suspended">Gesperrt</span>';
                }
            });

            // Append action icons.
            var profileUrl = "/user/profile.php?id=" + uid;
            var editUrl = "/user/editadvanced.php?id=" + uid;
            var actionsCell = document.createElement("td");
            actionsCell.style.whiteSpace = "nowrap";
            actionsCell.innerHTML = '<div class="lernhive-participant-actions">'
                + '<a href="' + profileUrl + '" class="lh-act-info" title="Info">' + svgInfo + '</a>'
                + '<a href="' + editUrl + '" class="lh-act-edit" title="Bearbeiten">' + svgEdit + '</a>'
                + '<a href="' + editUrl + '" class="lh-act-delete" title="Entfernen">' + svgDelete + '</a>'
                + '</div>';
            row.appendChild(actionsCell);
        });
    }
    addStatusBadgesAndActions();
    // Re-run on AJAX table reload.
    var actionsObserver = new MutationObserver(function() { addStatusBadgesAndActions(); });
    var tbodyEl = document.querySelector("#participants tbody") || document.getElementById("region-main");
    if (tbodyEl) actionsObserver.observe(tbodyEl, { childList: true, subtree: true });
});
</script>
JS;

        $hook->add_html($css . $js);
    }

    /**
     * Inject JavaScript to simplify the activity chooser modal based on level.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    /**
     * Initialise the Moodle activity chooser for teachers.
     *
     * Moodle only initialises core_courseformat/activitychooser when
     * `open-addingcontent` dropdown elements are present (admin view).
     * For teachers who only have between-activity `open-chooser` buttons,
     * the module is never loaded, so clicking the + button does nothing.
     *
     * This fix explicitly calls activitychooser.init() and makes the
     * between-activity + buttons permanently visible (instead of hover-only).
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    private static function inject_activity_chooser_init(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $COURSE;

        $courseid = $COURSE->id ?? 0;
        if (!$courseid) {
            return;
        }

        // Ensure the activity chooser AMD module is initialized.
        // In standard Moodle 5.x the course format renderer calls ac.init(),
        // but LernHive's capability filtering can prevent the renderer from
        // outputting that call. We unconditionally init after DOM is ready.
        // ac.init() is safe to call even if already initialized — it checks
        // internally. We must NOT guard on button existence because this
        // script runs at top-of-body before course content is rendered.
        $contextid = \context_course::instance($courseid)->id;
        $html = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    require(['core_courseformat/activitychooser'], function(ac) {
        ac.init({$courseid}, {$contextid});
    });
});
</script>
<style>
/* LernHive: Make between-activity add-content buttons always visible in edit mode. */
body.editing .course-section .divider .divider-content {
    visibility: visible !important;
    opacity: 1 !important;
}
body.editing .course-section .divider .divider-content .btn.add-content {
    visibility: visible !important;
}
/* LernHive Explorer: Hide "Subsection" option in the + dropdown for Level 1. */
body .course-section .add-content .dropdown-menu [data-action="addSection"],
body .course-section .add-content .dropdown-menu [data-value="subsection"],
body .course-content .add-content .dropdown-menu [data-action="addSection"],
body .course-content .add-content .dropdown-menu [data-value="subsection"],
body .divider .dropdown-menu [data-action="addSection"],
body .divider .dropdown-menu [data-value="subsection"] {
    display: none !important;
}
</style>
<script>
// LernHive Explorer: Hide "Subsection" from + dropdown (fallback by text match).
(function() {
    "use strict";
    function hideSubsection(root) {
        root.querySelectorAll('.dropdown-menu .dropdown-item, .dropdown-menu a, .dropdown-menu button').forEach(function(item) {
            var text = (item.textContent || "").trim().toLowerCase();
            if (text === "subsection" || text === "unterabschnitt") {
                item.style.display = "none";
            }
        });
    }
    var obs = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var nodes = mutations[i].addedNodes;
            for (var j = 0; j < nodes.length; j++) {
                if (nodes[j].nodeType === 1) hideSubsection(nodes[j]);
            }
        }
    });
    if (document.body) {
        obs.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener("DOMContentLoaded", function() {
            obs.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
</script>
HTML;

        $hook->add_html($html);
    }

    private static function inject_activity_chooser_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        // CSS-first approach: CSS hides UI elements reliably regardless of
        // when Moodle's async modal renders. Selectors match Moodle 5.x
        // activity chooser HTML structure (inspected live April 2026).
        // JS handles content-aware modifications (tags, gradable, links).
        $html = <<<'HTML'
<style>
/* === LernHive Explorer: Activity Chooser Simplification === */

/* 1. Hide search bar */
.modal .modchooserfilters,
.modal [role="search"] {
    display: none !important;
}

/* 2. Hide category sidebar/tabs */
.modal .modchoosernav,
.modal .modchoosercontainer > [role="tablist"] {
    display: none !important;
}

/* 3. Force first tabpanel visible */
.modal .modchoosercontainer [role="tabpanel"]:first-of-type {
    display: block !important;
    visibility: visible !important;
}
.modal .modchoosercontainer [role="tabpanel"]:first-of-type[hidden] {
    display: block !important;
}

/* 4. Hide non-first tabpanels */
.modal .modchoosercontainer [role="tabpanel"] ~ [role="tabpanel"] {
    display: none !important;
}

/* 5. Hide "All" heading */
.modal [data-region="modules"] h6,
.modal .modchoosercontainer [role="tabpanel"] > h6 {
    display: none !important;
}

/* 6. CRITICAL: Force activity list to vertical single-column layout.
   Moodle 5.x .optionscontainer uses CSS grid with 4 columns — override. */
.modal .optionscontainer[role="menubar"] {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
}

/* 7. Compact modal — fits 6 items without scroll. */
.modal:has(.modchoosercontainer) .modal-dialog {
    max-width: 460px !important;
}
.modal:has(.modchoosercontainer) .modal-body {
    max-height: none !important;
    overflow: visible !important;
    padding: 8px 16px !important;
}
.modal:has(.modchoosercontainer) .carousel-item[data-region="modules"] {
    padding: 4px 0 !important;
}
.modal .modchoosercontainer {
    padding: 0 !important;
}

/* 8. Activity item row — clean horizontal layout. */
.modal .optionscontainer [role="menuitem"] {
    display: flex !important;
    align-items: center !important;
    width: 100% !important;
    padding: 8px 10px !important;
    border-radius: 8px;
    gap: 10px;
}
.modal .optionscontainer [role="menuitem"]:hover {
    background: #e8f0fe;
}
/* Activity name: let it take full space. */
.modal .optionscontainer [role="menuitem"] .optionname {
    flex: 1 !important;
    min-width: 0;
    font-size: 14px;
    font-weight: 500;
}
/* Activity icon. */
.modal .optionscontainer .activityiconcontainer {
    width: 32px !important;
    height: 32px !important;
    flex-shrink: 0;
}

/* 9. Hide original action buttons (star + info) — made invisible but clickable
   so JS can still trigger them. Do NOT use display:none. */
.modal .optionscontainer .optionactions {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
    padding: 0 !important;
    margin: -1px !important;
}

/* 10. LernHive "Mehr Infos" + "Hinzufügen" buttons.
   CRITICAL: z-index:2 to sit above the stretched-link::after (z-index:1)
   that Moodle puts on the a.optioninfo link covering the entire row. */
.lh-chooser-actions {
    display: flex !important;
    gap: 6px;
    margin-left: auto;
    flex-shrink: 0;
    align-items: center;
    position: relative;
    z-index: 2;
}
.lh-btn-info {
    background: #f0f4ff;
    color: #1a73e8;
    border: 1px solid #d2e3fc;
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s, border-color 0.15s;
}
.lh-btn-info:hover {
    background: #d2e3fc;
    border-color: #1a73e8;
}
.lh-btn-add {
    background: #1a73e8;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s;
}
.lh-btn-add:hover {
    background: #1557b0;
}

/* 11. Entire modal: pure white background. */
.modal:has(.modchoosercontainer) .modal-content {
    background: #fff !important;
}
.modal:has(.modchoosercontainer) .modal-header {
    background: #fff !important;
    border-bottom: 1px solid #e8e8e8;
}
.modal:has(.modchoosercontainer) .modal-footer {
    background: #fff !important;
}

/* 12. Info/detail view — hide right sidebar, full-width white layout. */
.modal .activitychooser-option-details,
.modal .modchooser-detail {
    display: none !important;
}
.modal .carousel-item .d-flex.flex-md-row-reverse,
.modal .optionsummary .d-flex.flex-md-row-reverse {
    display: block !important;
}
.modal .carousel-item hr,
.modal .optionsummary hr {
    display: none !important;
}
.modal .carousel-item {
    background: #fff !important;
    padding: 12px 16px !important;
}
.modal .carousel-item .flex-grow-1 {
    max-width: 100% !important;
    padding-right: 0 !important;
}
</style>
<script>
(function() {
    "use strict";

    function processChooserModal(modal) {
        if (modal.dataset.lernhiveProcessed) return;
        modal.dataset.lernhiveProcessed = "1";

        // Ensure the first tabpanel is visible.
        var panels = modal.querySelectorAll('[role="tabpanel"]');
        if (panels.length > 0) {
            panels[0].removeAttribute("hidden");
            panels[0].setAttribute("aria-hidden", "false");
            panels[0].classList.add("show", "active");
        }

        simplifyContent(modal);

        // Watch for dynamic content changes (info view, carousel transitions).
        var innerObserver = new MutationObserver(function() {
            simplifyContent(modal);
        });
        innerObserver.observe(modal, { childList: true, subtree: true });
    }

    function simplifyContent(container) {
        // --- Add "Mehr Infos" + "Hinzufügen" buttons to each activity ---
        container.querySelectorAll('[role="menuitem"]').forEach(function(item) {
            if (item.dataset.lernhiveBtns) return;
            // Skip items inside the info/detail carousel.
            if (item.closest('.carousel-item[data-region="help"]')) return;

            item.dataset.lernhiveBtns = "1";

            var infoBtn = item.querySelector('[data-action="show-option-summary"]');
            var addLink = item.querySelector('a[href*="mod.php"]');

            // Create our button container — append to the menuitem itself.
            var lhActions = document.createElement("div");
            lhActions.className = "lh-chooser-actions";

            var moreBtn = document.createElement("button");
            moreBtn.type = "button";
            moreBtn.className = "lh-btn-info";
            moreBtn.textContent = "Mehr Infos";
            moreBtn.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Moodle needs the button to be "visible" for click handlers.
                // Temporarily un-clip, trigger click, then re-clip.
                if (infoBtn) {
                    var group = infoBtn.closest('.optionactions');
                    if (group) {
                        group.style.cssText = "position:absolute;opacity:0;pointer-events:auto;width:auto;height:auto;clip:auto;overflow:visible;";
                    }
                    infoBtn.click();
                    if (group) {
                        // Let Moodle's handler fire before re-hiding.
                        setTimeout(function() {
                            group.style.cssText = "";
                        }, 50);
                    }
                }
            });

            var addBtnEl = document.createElement("button");
            addBtnEl.type = "button";
            addBtnEl.className = "lh-btn-add";
            addBtnEl.textContent = "Hinzufügen";
            addBtnEl.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (addLink) {
                    window.location.href = addLink.href;
                }
            });

            lhActions.appendChild(moreBtn);
            lhActions.appendChild(addBtnEl);
            item.appendChild(lhActions);
        });

        // --- Hide "Gradable" and tag labels in info view ---
        container.querySelectorAll('.activitychooser-option-details').forEach(function(el) {
            el.style.display = "none";
        });

        // --- Rewrite "More help" links ---
        container.querySelectorAll('a').forEach(function(link) {
            var href = link.getAttribute("href") || "";
            var match = href.match(/docs\.moodle\.org\/[^\/]*\/en\/mod\/([a-z0-9_]+)/i)
                     || href.match(/docs\.moodle\.org\/.*\/mod\/([a-z0-9_]+)/i);
            if (match) {
                var modname = match[1].replace(/\/.*$/, "");
                link.setAttribute("href", "https://lernhive.de/help/" + modname);
                link.setAttribute("target", "_blank");
            }
        });
    }

    // --- MutationObserver: detect activity chooser modal ---
    var bodyObserver = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var nodes = mutations[i].addedNodes;
            for (var j = 0; j < nodes.length; j++) {
                var node = nodes[j];
                if (node.nodeType !== 1) continue;
                var targets = [];
                if (node.querySelector) {
                    node.querySelectorAll('.modchoosercontainer, [data-region="modules"]').forEach(function(m) {
                        targets.push(m.closest('.modal') || m);
                    });
                }
                if (node.classList && node.classList.contains('modal') && node.querySelector('.modchoosercontainer')) {
                    targets.push(node);
                }
                targets.forEach(function(t) { processChooserModal(t); });
            }
        }
    });

    if (document.body) {
        bodyObserver.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener("DOMContentLoaded", function() {
            bodyObserver.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
</script>
HTML;

        $hook->add_html($html);
    }

    /**
     * Inject CSS + JS to simplify the Moodle filepicker.
     *
     * Level 1 (Explorer):
     * - Keep: Server files, Upload a file, URL downloader
     * - Hide: Content bank, Recent files, Private files, Wikimedia, and any other repos
     *
     * Uses an allow-list approach so any future/unknown repos are hidden by default.
     * A MutationObserver watches for dynamically loaded filepicker dialogs.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_filepicker_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        $js = <<<'JS'
<script>
(function() {
    "use strict";

    // Allow-list of repo names (lowercase) for Level Explorer.
    // Includes English and German variants.
    var allowedRepos = [
        "server files", "server-dateien",
        "upload a file", "datei hochladen",
        "url downloader", "url-downloader"
    ];

    /**
     * Check if a repo name is in the allow-list.
     */
    function isAllowed(repoName) {
        var name = (repoName || "").trim().toLowerCase();
        for (var i = 0; i < allowedRepos.length; i++) {
            if (name === allowedRepos[i]) return true;
        }
        return false;
    }

    /**
     * Filter repos inside a filepicker repo area.
     * Hides any .fp-repo whose .fp-repo-name text is not in the allow-list.
     */
    function filterRepoArea(repoArea) {
        var repos = repoArea.querySelectorAll(".fp-repo");
        repos.forEach(function(repo) {
            var nameEl = repo.querySelector(".fp-repo-name");
            if (!nameEl) return;
            var name = nameEl.textContent.trim();
            if (!isAllowed(name)) {
                repo.style.display = "none";
            } else {
                repo.style.display = "";
            }
        });
    }

    /**
     * Scan a node (or entire document) for all filepicker repo areas and filter them.
     */
    function filterAll(root) {
        var areas = (root || document).querySelectorAll(".fp-repo-area");
        areas.forEach(function(area) {
            filterRepoArea(area);
        });
    }

    // MutationObserver to catch dynamically loaded filepicker dialogs.
    var observer = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var mutation = mutations[i];
            for (var j = 0; j < mutation.addedNodes.length; j++) {
                var node = mutation.addedNodes[j];
                if (node.nodeType !== 1) continue;

                // Check if the added node IS a repo area or CONTAINS one.
                if (node.classList && node.classList.contains("fp-repo-area")) {
                    filterRepoArea(node);
                } else if (node.querySelectorAll) {
                    var areas = node.querySelectorAll(".fp-repo-area");
                    if (areas.length > 0) {
                        areas.forEach(function(area) { filterRepoArea(area); });
                    }
                }
            }
        }
    });

    // Also handle repos that load asynchronously inside already-visible dialogs.
    // Some filepicker implementations load repos after the modal is in the DOM.
    var repoObserver = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var mutation = mutations[i];
            for (var j = 0; j < mutation.addedNodes.length; j++) {
                var node = mutation.addedNodes[j];
                if (node.nodeType !== 1) continue;
                if (node.classList && node.classList.contains("fp-repo")) {
                    var nameEl = node.querySelector(".fp-repo-name");
                    if (nameEl && !isAllowed(nameEl.textContent)) {
                        node.style.display = "none";
                    }
                }
            }
        }
    });

    function startObserving() {
        // Observe body for new filepicker dialogs.
        observer.observe(document.body, { childList: true, subtree: true });

        // Also observe existing and future .fp-repo-area containers for late-loaded repos.
        repoObserver.observe(document.body, { childList: true, subtree: true });

        // Filter any already-present filepicker areas (e.g., inline filepickers).
        filterAll(document);
    }

    if (document.body) {
        startObserving();
    } else {
        document.addEventListener("DOMContentLoaded", startObserving);
    }
})();
</script>
JS;

        $hook->add_html($js);
    }

    /**
     * Inject CSS + JS to simplify activity edit forms.
     *
     * Level 1 (Explorer):
     * - Hide: Appearance, Common module settings, Restrict access,
     *         Completion conditions, Set reminder in Timeline,
     *         Tags, Competencies
     * - Hide: "Send content change notification" checkbox
     * - Keep: General, Select files (and any activity-specific sections)
     *
     * Uses CSS for instant hide (no flicker) plus JS fallback for the
     * notification checkbox which has no unique fieldset.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_activity_form_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        if ($level > 1) {
            return;
        }

        $html = <<<'HTML'
<style>
/* LernHive Explorer: hide advanced activity form sections */
#id_optionssection,
#id_modstandardelshdr,
#id_availabilityconditionsheader,
#id_activitycompletionheader,
#id_completionexpected,
#id_tagshdr,
#id_competenciessection {
    display: none !important;
}
/* Hide "Send content change notification" checkbox row */
#id_coursecontentnotification {
    display: none !important;
}
/* Also hide parent .fitem row of the notification checkbox */
.fitem:has(#id_coursecontentnotification) {
    display: none !important;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    "use strict";

    // Fallback for browsers without :has() support —
    // hide the .fitem parent of the notification checkbox.
    var notifCheckbox = document.getElementById("id_coursecontentnotification");
    if (notifCheckbox) {
        var fitem = notifCheckbox.closest(".fitem");
        if (fitem) {
            fitem.style.display = "none";
        }
    }
});
</script>
HTML;

        $hook->add_html($html);
    }

    /**
     * Inject full dashboard content: Welcome Banner, Quick Stats, Tour-Tiles, Action Buttons.
     *
     * Replaces the old inject_dashboard_buttons. All content is inserted via JS
     * at the top of #region-main on the dashboard page.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    private static function inject_dashboard_content(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        global $USER, $DB, $PAGE;

        // Skip sidebar injection when LernHive theme is active — it has its
        // own sidebar. We still inject the welcome banner and stats below;
        // only the <nav id="lernhive-sidebar"> block is suppressed.
        $skipSidebar = ($PAGE->theme->name === 'lernhive');

        $levelname = level_manager::get_level_name($level);
        $firstname = s($USER->firstname);

        // ── Lucide SVG icons (inline, outline style) ──
        $icons = [
            'compass'   => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
            'book-open' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'users'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>',
            'trending'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
            'plus'      => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
            'user-plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
            'map'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>',
            'life-buoy' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>',
        ];

        // ── Tour progress data ──
        $tourprogress = null;
        $tourtiles = '';
        $toursurl = new \moodle_url('/local/lernhive_start/tours.php');

        // Check if local_lernhive_start is installed.
        $startplugininstalled = $DB->get_manager()->table_exists('local_lernhive_start_cats');
        if ($startplugininstalled && class_exists('\\local_lernhive_start\\tour_manager')) {
            $tourprogress = \local_lernhive_start\tour_manager::get_level_progress($level, $USER->id);

            // Lucide SVG icon map (keyed by icon name from DB).
            $lucideicons = [
                'user-plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
                'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                'book-plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><line x1="12" y1="8" x2="12" y2="14"/><line x1="9" y1="11" x2="15" y2="11"/></svg>',
                'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
                'plus-square' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
                'message-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>',
            ];

            // Build tour tiles HTML (max 3, horizontal layout, with modal popup).
            $modalshtml = '';
            if (!empty($tourprogress['categories'])) {
                $showncount = 0;
                foreach ($tourprogress['categories'] as $cat) {
                    if ($showncount >= 3) {
                        break;
                    }
                    $showncount++;

                    // Resolve display name: try lang string, fall back to DB name.
                    $strmanager = get_string_manager();
                    if ($strmanager->string_exists($cat->name, 'local_lernhive_start')) {
                        $catname = s(get_string($cat->name, 'local_lernhive_start'));
                    } else {
                        $catname = s($cat->name);
                    }

                    // Resolve description: try lang string, fall back to empty.
                    $desckey = $cat->name . '_desc'; // e.g. tourcat_create_users_desc
                    if ($strmanager->string_exists($desckey, 'local_lernhive_start')) {
                        $catdesc = s(get_string($desckey, 'local_lernhive_start'));
                    } else {
                        $catdesc = '';
                    }

                    $catcolor = s($cat->color);
                    $caticon = $lucideicons[$cat->icon] ?? '';
                    $catprogress = $cat->progress;
                    $pct = $catprogress['percent'];
                    $catid = (int) $cat->id;
                    $progresstext = $catprogress['completed'] . '/' . $catprogress['total'];

                    $tourtiles .= <<<TILE
<div class="lernhive-tour-tile" data-catid="{$catid}" onclick="document.getElementById('lernhive-modal-{$catid}').classList.add('open')" style="cursor:pointer;">
  <div class="lernhive-tour-icon" style="background:{$catcolor}22;color:{$catcolor};">{$caticon}</div>
  <div class="lernhive-tour-tile-body">
    <div class="lernhive-tour-name">{$catname}</div>
    <div class="lernhive-tour-progress">
      <div class="lernhive-tour-progress-fill" style="width:{$pct}%"></div>
    </div>
    <div class="lernhive-tour-progress-text">{$progresstext} Touren</div>
  </div>
</div>
TILE;

                    // Build modal content: list of tours in this category.
                    $tourlisthtml = '';
                    $sesskey = sesskey();
                    foreach ($catprogress['tours'] as $tour) {
                        $tourid = (int) $tour->tourid;
                        // Get tour name from Moodle's user tours table.
                        $tourrecord = $DB->get_record('tool_usertours_tours', ['id' => $tourid], 'name');
                        $tourname = $tourrecord ? s($tourrecord->name) : "Tour {$tourid}";
                        // Remove "LernHive: " prefix if present for cleaner display.
                        $tourname = preg_replace('/^LernHive:\s*/i', '', $tourname);
                        $starturl = (new \moodle_url('/local/lernhive_start/starttour.php', [
                            'tourid' => $tourid,
                            'sesskey' => $sesskey,
                        ]))->out(true);
                        $completedclass = $tour->completed ? 'completed' : '';
                        $checkicon = $tour->completed
                            ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                            : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>';
                        $btnlabel = $tour->completed ? 'Wiederholen' : 'Starten';

                        $tourlisthtml .= <<<TOUR
<a href="{$starturl}" class="lernhive-modal-tour {$completedclass}">
  <span class="lernhive-modal-tour-icon">{$checkicon}</span>
  <span class="lernhive-modal-tour-name">{$tourname}</span>
  <span class="lernhive-modal-tour-btn">{$btnlabel} →</span>
</a>
TOUR;
                    }

                    $modalshtml .= <<<MODAL
<div class="lernhive-modal-overlay" id="lernhive-modal-{$catid}" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="lernhive-modal">
    <div class="lernhive-modal-header" style="color:{$catcolor};">
      <div class="lernhive-modal-header-icon" style="background:{$catcolor}22;color:{$catcolor};">{$caticon}</div>
      <h3>{$catname}</h3>
      <button class="lernhive-modal-close" onclick="this.closest('.lernhive-modal-overlay').classList.remove('open')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="lernhive-modal-desc">{$catdesc}</div>
    <div class="lernhive-modal-progress">
      <div class="lernhive-modal-progress-bar">
        <div class="lernhive-modal-progress-fill" style="width:{$pct}%;background:{$catcolor};"></div>
      </div>
      <span class="lernhive-modal-progress-text">{$progresstext} abgeschlossen</span>
    </div>
    <div class="lernhive-modal-tours">{$tourlisthtml}</div>
  </div>
</div>
MODAL;
                }
            }
        }

        // ── Quick Stats data ──
        $coursecount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT e.courseid)
               FROM {enrol} e
               JOIN {user_enrolments} ue ON ue.enrolid = e.id
              WHERE ue.userid = ?",
            [$USER->id]
        );

        // Count enrolled students across teacher's courses.
        $studentcount = 0;
        $teachercourses = $DB->get_fieldset_sql(
            "SELECT DISTINCT e.courseid
               FROM {enrol} e
               JOIN {user_enrolments} ue ON ue.enrolid = e.id
              WHERE ue.userid = ?",
            [$USER->id]
        );
        if (!empty($teachercourses)) {
            list($insql, $params) = $DB->get_in_or_equal($teachercourses);
            $studentcount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ue.userid)
                   FROM {enrol} e
                   JOIN {user_enrolments} ue ON ue.enrolid = e.id
                  WHERE e.courseid {$insql} AND ue.userid != ?",
                array_merge($params, [$USER->id])
            );
        }

        $tourpct = $tourprogress ? $tourprogress['percent'] : 0;
        $tourtotal = $tourprogress ? $tourprogress['total_tours'] : 0;

        // ── Action Buttons ──
        $allowcourse = get_config('local_lernhive', 'allow_teacher_course_creation');
        $allowuser = get_config('local_lernhive', 'allow_teacher_user_creation');
        $allowbrowse = get_config('local_lernhive', 'allow_teacher_user_browse');

        if ($allowbrowse) {
            self::ensure_user_browse_capability();
        }

        $actionbuttons = '';
        $actionbuttons .= '<a href="' . $toursurl->out(true) . '" '
            . 'class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 me-2">'
            . $icons['map'] . ' ' . s(get_string('onboarding_nav_link', 'local_lernhive')) . '</a>';

        if ($allowcourse) {
            $courseurl = course_manager::get_create_course_url($USER->id);
            $actionbuttons .= '<a href="' . $courseurl->out(true) . '" '
                . 'class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 me-2">'
                . $icons['plus'] . ' ' . s(get_string('btn_create_course', 'local_lernhive')) . '</a>';
        }

        if ($allowuser) {
            self::ensure_user_create_capability();
            $userurl = new \moodle_url('/user/editadvanced.php', ['id' => -1]);
            $actionbuttons .= '<a href="' . $userurl->out(true) . '" '
                . 'class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">'
                . $icons['user-plus'] . ' ' . s(get_string('btn_create_user', 'local_lernhive')) . '</a>';
        }

        // ── Build the SVG progress ring ──
        $circumference = 2 * M_PI * 24; // r=24 → ~150.8
        $dashoffset = $circumference - ($circumference * $tourpct / 100);

        // ── Welcome Section HTML (clean white, graphics-only for level/progress) ──
        $welcomehtml = <<<BANNER
<div class="lernhive-welcome-section">
  <div class="lernhive-welcome-left">
    <h1 class="lernhive-welcome-heading">Willkommen, {$firstname}!</h1>
  </div>
  <div class="lernhive-welcome-right">
    <div class="lernhive-level-badge">
      <div class="lernhive-level-badge-circle">{$icons['compass']}</div>
      <div class="lernhive-level-badge-text"><strong>{$levelname}</strong></div>
    </div>
    <div class="lernhive-progress-ring">
      <div class="lernhive-progress-circle">
        <svg viewBox="0 0 60 60">
          <circle cx="30" cy="30" r="24" fill="none" stroke="#e9e9e9" stroke-width="4"/>
          <circle cx="30" cy="30" r="24" fill="none" stroke="#f98012" stroke-width="4"
                  stroke-dasharray="{$circumference}" stroke-dashoffset="{$dashoffset}"
                  stroke-linecap="round" style="transform:rotate(-90deg);transform-origin:center;"/>
        </svg>
        <div class="lernhive-progress-circle-text">
          <strong>{$tourpct}%</strong>
        </div>
      </div>
      <div class="lernhive-progress-ring-label"><strong>Onboarding</strong></div>
    </div>
  </div>
</div>
BANNER;

        // ── Quick Stats HTML ──
        $mycoursesurl = (new \moodle_url('/course/index.php'))->out(true);
        $userlisturl  = $allowbrowse
            ? (new \moodle_url('/local/lernhive/users.php'))->out(true)
            : '#';
        $supporturl = (new \moodle_url('/local/lernhive/support.php'))->out(true);

        $statshtml = <<<STATS
<div class="lernhive-stats">
  <a href="{$mycoursesurl}" class="lernhive-stat-card lernhive-stat-link">
    <div class="lernhive-stat-icon blue">{$icons['book-open']}</div>
    <div class="lernhive-stat-value">{$coursecount}</div>
    <div class="lernhive-stat-label">Aktive Kurse</div>
  </a>
  <a href="{$userlisturl}" class="lernhive-stat-card lernhive-stat-link">
    <div class="lernhive-stat-icon lightblue">{$icons['users']}</div>
    <div class="lernhive-stat-value">{$studentcount}</div>
    <div class="lernhive-stat-label">Nutzer/innen gesamt</div>
  </a>
  <a href="{$supporturl}" class="lernhive-stat-card lernhive-stat-link">
    <div class="lernhive-stat-icon teal">{$icons['life-buoy']}</div>
    <div class="lernhive-stat-value">&nbsp;</div>
    <div class="lernhive-stat-label">Support zur Nutzung</div>
  </a>
  <div class="lernhive-stat-card">
    <div class="lernhive-stat-icon green">{$icons['trending']}</div>
    <div class="lernhive-stat-value">{$tourpct}%</div>
    <div class="lernhive-stat-label">Onboarding</div>
  </div>
</div>
STATS;

        // ── Tour Tiles HTML ──
        $tourtileshtml = '';
        if (!empty($tourtiles)) {
            $tourtileshtml = <<<TOURS
<div class="lernhive-section-header">
  <h2>Onboarding</h2>
  <a href="{$toursurl->out(true)}" class="lernhive-view-all">Alle Touren anzeigen →</a>
</div>
<div class="lernhive-tour-grid">{$tourtiles}</div>
TOURS;
        }

        // ── Section Header for Courses ──
        $coursesectionheader = <<<CHEADER
<div class="lernhive-section-header" style="margin-top:8px;">
  <h2>Meine Kurse</h2>
</div>
CHEADER;

        // ── Combine all parts ──
        // Escape single quotes for JS insertion.
        $allcontent = str_replace("'", "\\'", $welcomehtml . $statshtml . $tourtileshtml);
        $buttonsescaped = str_replace("'", "\\'", $actionbuttons);
        $coursesectionescaped = str_replace("'", "\\'", $coursesectionheader);
        // Escape modals HTML for JS.
        $modalsescaped = str_replace("'", "\\'", $modalshtml);
        $modalsescaped = str_replace(["\r\n", "\n", "\r"], '', $modalsescaped);

        // Escape newlines for JS.
        $allcontent = str_replace(["\r\n", "\n", "\r"], '', $allcontent);
        $buttonsescaped = str_replace(["\r\n", "\n", "\r"], '', $buttonsescaped);
        $coursesectionescaped = str_replace(["\r\n", "\n", "\r"], '', $coursesectionescaped);

        // ── Sidebar Navigation HTML ──
        $messageurl = new \moodle_url('/message/index.php');
        $profileurl = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        $prefsurl = new \moodle_url('/user/preferences.php');
        $langurl = new \moodle_url('/user/language.php');

        // Lucide SVG inline icons for sidebar.
        $svgdashboard = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
        $svgmessage = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>';
        $svglernpfade = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>';
        $svgbook = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>';
        $svgplus = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
        $svguserplus = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>';
        $svgprofile = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
        $svgsettings = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/></svg>';
        $svglang = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';

        // ── Build "Kurse" section: up to 10 courses ──
        $courselistitems = '';
        $usercourses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname', 'fullname ASC');
        $coursecount2 = 0;
        foreach ($usercourses as $c) {
            if ($coursecount2 >= 10) {
                break;
            }
            $coursecount2++;
            $cname = s(format_string($c->fullname, true));
            $curl = (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(true);
            $courselistitems .= '<a class="lernhive-nav-item lernhive-nav-course" href="' . $curl . '">'
                . $svgbook . ' ' . $cname . '</a>';
        }
        $coursessection = '';
        if (!empty($courselistitems)) {
            $allcoursesurl = (new \moodle_url('/course/index.php'))->out(true);
            $coursessection = '<div class="lernhive-nav-section">'
                . '<div class="lernhive-nav-label">Kurse</div>'
                . $courselistitems
                . '<a class="lernhive-nav-item lernhive-nav-viewall" href="' . $allcoursesurl . '">'
                . 'Alle Kurse →</a>'
                . '</div>';
        }

        // ── Build "System" section ──
        $systemitems = '';
        if ($allowcourse) {
            $courseurl = course_manager::get_create_course_url($USER->id);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $courseurl->out(true) . '">'
                . $svgplus . ' Kurs anlegen</a>';
        }
        if ($allowuser) {
            $userurl = new \moodle_url('/user/editadvanced.php', ['id' => -1]);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $userurl->out(true) . '">'
                . $svguserplus . ' Nutzer/in anlegen</a>';
        }
        if ($allowbrowse) {
            $svgusers = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
            $userlistnavurl = (new \moodle_url('/local/lernhive/users.php'))->out(true);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $userlistnavurl . '">'
                . $svgusers . ' Nutzer/innen</a>';
        }
        $systemitems .= '<a class="lernhive-nav-item" href="' . $profileurl->out(true) . '">'
            . $svgprofile . ' Profil</a>';
        $systemitems .= '<a class="lernhive-nav-item" href="' . $prefsurl->out(true) . '">'
            . $svgsettings . ' Einstellungen</a>';

        // Admin link (only for site admins).
        if (is_siteadmin()) {
            $adminurl = (new \moodle_url('/admin/search.php'))->out(true);
            $svgadmin = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
            $systemitems .= '<a class="lernhive-nav-item" href="' . $adminurl . '">'
                . $svgadmin . ' Administration</a>';
        }

        // "Nutzer/innen" link in main section (only if browse setting enabled).
        $usernav = '';
        if ($allowbrowse) {
            $svgusersnav = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
            $userlistnavurl2 = (new \moodle_url('/local/lernhive/users.php'))->out(true);
            $usernav = '<a class="lernhive-nav-item" href="' . $userlistnavurl2 . '">'
                . $svgusersnav . ' Nutzer/innen</a>';
        }

        $svglifebuoy = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>';
        $sidebarhtml = '<nav class="lernhive-sidebar" id="lernhive-sidebar">'
            . '<div class="lernhive-nav-section">'
            . '<a class="lernhive-nav-item active" href="/my/">' . $svgdashboard . ' Dashboard</a>'
            . '<a class="lernhive-nav-item" href="' . $messageurl->out(true) . '">' . $svgmessage . ' Nachrichten</a>'
            . $usernav
            . '<a class="lernhive-nav-item" href="' . $toursurl->out(true) . '">' . $svglernpfade . ' Onboarding</a>'
            . '<a class="lernhive-nav-item" href="' . $supporturl . '">' . $svglifebuoy . ' Support</a>'
            . '</div>'
            . $coursessection
            . '<div class="lernhive-nav-section">'
            . '<div class="lernhive-nav-label">System</div>'
            . $systemitems
            . '</div>'
            . '</nav>';
        $sidebarescaped = str_replace("'", "\\'", str_replace(["\r\n", "\n", "\r"], '', $sidebarhtml));

        $html = <<<HTML
<script>
document.addEventListener("DOMContentLoaded", function() {
    var mainContent = document.getElementById("region-main");
    if (!mainContent) return;

    // 1. Insert Welcome + Stats + Tour-Tiles at the top.
    var dashContent = document.createElement("div");
    dashContent.className = "lernhive-dashboard-content";
    dashContent.innerHTML = '{$allcontent}';
    mainContent.prepend(dashContent);

    // 2. Insert action buttons after the dashboard content.
    var actionsWrapper = document.createElement("div");
    actionsWrapper.className = "lernhive-dashboard-actions";
    actionsWrapper.innerHTML = '{$buttonsescaped}';
    dashContent.after(actionsWrapper);

    // 3. Insert "Meine Kurse" section header before the course overview block.
    var courseBlock = mainContent.querySelector("[data-region='myoverview'], .block-myoverview, .block_myoverview");
    if (courseBlock) {
        var courseHeader = document.createElement("div");
        courseHeader.innerHTML = '{$coursesectionescaped}';
        courseBlock.parentNode.insertBefore(courseHeader, courseBlock);
    }

    // 4. Hide redundant elements.
    // Hide page header area (Dashboard heading, greeting, Customise button).
    var pageHeader = document.getElementById("page-header");
    if (pageHeader) pageHeader.style.display = "none";
    // Hide "Hi, ..." greeting.
    mainContent.querySelectorAll("h2, h3").forEach(function(h) {
        if (h.textContent.trim().match(/^Hi,/)) h.style.display = "none";
    });

    // 5. Hide Moodle's primary nav tabs (Dashboard/More) — we use sidebar instead.
    var primaryNav = document.querySelector(".primary-navigation, nav.moremenu");
    if (primaryNav) primaryNav.style.display = "none";

    // 6. Hide Moodle's left drawer toggle and drawer — we inject our own sidebar.
    var drawerLeft = document.querySelector(".drawer.drawer-left, #theme_boost-drawers-courseindex");
    if (drawerLeft) drawerLeft.style.display = "none";
    document.querySelectorAll("button[data-toggler='drawers']").forEach(function(btn) {
        var controls = btn.getAttribute("aria-controls") || "";
        if (controls.indexOf("courseindex") >= 0 || controls.indexOf("index") >= 0) {
            btn.style.display = "none";
        }
    });
    // Also hide the side-panel toggle.
    var sidePanel = document.querySelector("[data-region='sidepanel']");
    if (sidePanel) sidePanel.style.display = "none";

    // 7. Inject LernHive sidebar navigation (only when theme_lernhive is NOT active).
    // theme_lernhive provides its own <aside class="lernhive-sidebar"> — injecting
    // a second <nav id="lernhive-sidebar"> would create a duplicate fixed element.
    if (typeof lhThemeHasSidebar === 'undefined') {
    var sidebarEl = document.createElement("div");
    sidebarEl.innerHTML = '{$sidebarescaped}';
    document.body.appendChild(sidebarEl.firstElementChild);
    }

    // 8. Clean up course overview block (Explorer simplification).
    if (courseBlock) {
        var blk = courseBlock.closest(".block") || courseBlock;

        // Hide ALL headings inside the block (Kursübersicht, Course overview, etc.).
        blk.querySelectorAll(".card-title, h5, h3, h4, .header-body-text").forEach(function(h) {
            var txt = h.textContent.trim();
            if (/Course overview|Kursübersicht|Kurs.?bersicht/i.test(txt)) {
                h.style.display = "none";
            }
        });
        // Also try: any element whose text is exactly the block title.
        blk.querySelectorAll("span, div, p").forEach(function(el) {
            var txt = el.textContent.trim();
            if (el.children.length === 0 && /^(Kursübersicht|Course overview)$/i.test(txt)) {
                el.style.display = "none";
            }
        });

        // Hide "Kurs erstellen" button.
        blk.querySelectorAll("a, button").forEach(function(el) {
            var txt = (el.textContent || "").trim();
            if (/Kurs erstellen|Create course|New course/i.test(txt)) {
                el.style.display = "none";
            }
        });

        // Remove card chrome from block wrapper.
        blk.style.boxShadow = "none";
        blk.style.border = "none";
        blk.style.background = "transparent";

        // Hide filter dropdowns (Alle, Sortiert nach, Kachel/Liste).
        blk.querySelectorAll("[data-action='grouping'], [data-action='display'], [data-action='sort']").forEach(function(btn) {
            var dd = btn.closest(".dropdown") || btn;
            dd.style.display = "none";
        });
        // Also hide by data-region.
        blk.querySelectorAll("[data-region='filter'], [data-region='grouping-dropdown'], [data-region='display-dropdown'], [data-region='sort-dropdown']").forEach(function(el) {
            el.style.display = "none";
        });

        // Hide three-dot action menu on course cards.
        blk.querySelectorAll(".course-card-actions, .coursemenubtn, [data-region='course-card-actions']").forEach(function(el) {
            el.style.display = "none";
        });
        // Also catch dynamically rendered menus via observer.
        var cardObserver = new MutationObserver(function() {
            blk.querySelectorAll(".course-card-actions, .coursemenubtn, [data-region='course-card-actions']").forEach(function(el) {
                el.style.display = "none";
            });
        });
        cardObserver.observe(blk, { childList: true, subtree: true });
    }

    // 9. Add Search button next to the myoverview search input.
    if (courseBlock) {
        var searchInput = courseBlock.querySelector("input[data-action='search'], input[type='text'][placeholder]");
        if (searchInput && !searchInput.parentNode.querySelector(".lh-search-btn")) {
            searchInput.style.borderRadius = "20px 0 0 20px";
            var searchBtn = document.createElement("button");
            searchBtn.className = "btn btn-primary lh-search-btn";
            searchBtn.type = "button";
            searchBtn.textContent = "Search";
            searchBtn.style.cssText = "border-radius:0 20px 20px 0;padding:6px 18px;font-size:0.9rem;font-weight:500;border:1px solid #194866;margin-left:-1px;";
            searchBtn.addEventListener("click", function() {
                // Trigger Moodle's search by dispatching input + keyup events.
                searchInput.dispatchEvent(new Event("input", {bubbles:true}));
                searchInput.dispatchEvent(new KeyboardEvent("keyup", {key:"Enter",code:"Enter",keyCode:13,bubbles:true}));
                searchInput.dispatchEvent(new KeyboardEvent("keydown", {key:"Enter",code:"Enter",keyCode:13,bubbles:true}));
            });
            searchInput.parentNode.insertBefore(searchBtn, searchInput.nextSibling);
            // Wrap both in a flex container if not already.
            var searchParent = searchInput.parentNode;
            searchParent.style.display = "flex";
            searchParent.style.alignItems = "center";
        }
    }

    // 10. Hide Zeitleiste / Timeline block entirely.
    document.querySelectorAll(".block_timeline, .block-timeline, [data-block='timeline']").forEach(function(el) {
        var block = el.closest(".block") || el;
        block.style.display = "none";
    });
    // Also try matching by heading text for broader compat.
    mainContent.querySelectorAll("h5, .card-title").forEach(function(h) {
        if (/Zeitleiste|Timeline/i.test(h.textContent.trim())) {
            var block = h.closest(".block");
            if (block) block.style.display = "none";
        }
    });

    // 11. Inject modals for onboarding tour tiles.
    var modalsWrapper = document.createElement("div");
    modalsWrapper.innerHTML = '{$modalsescaped}';
    document.body.appendChild(modalsWrapper);
});
</script>
HTML;

        $hook->add_html($html);
    }

    /**
     * Simplify the user creation form for LernHive teachers.
     *
     * Hides: username, auth method, force password change, additional names,
     * interests, optional section, user picture.
     * Auto-sets: username = email, auth = manual, forcepasswordchange = checked.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @param int $level The user's LernHive level.
     */
    /**
     * Ensure the current user can create users at system level.
     *
     * Teachers have their editingteacher role only in course contexts.
     * moodle/user:create requires system context. We create a lightweight
     * role 'lernhive_usercreator' and assign it to the user at system level.
     *
     * Called once per request when "allow_teacher_user_creation" is active.
     */
    private static function ensure_user_create_capability(): void {
        global $DB, $USER;
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $systemcontext = \context_system::instance();

        // Already has capability? Nothing to do.
        if (has_capability('moodle/user:create', $systemcontext)) {
            return;
        }

        // Only grant to users who have a LernHive level record.
        $record = level_manager::get_level_record($USER->id);
        if (!$record) {
            return;
        }

        // Get or create the lightweight role.
        $role = $DB->get_record('role', ['shortname' => 'lernhive_usercreator']);
        if (!$role) {
            $roleid = create_role(
                'LernHive User Creator',
                'lernhive_usercreator',
                'Lightweight role for LernHive teachers to create users',
                ''
            );
            // Allow assignment at system level.
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);

            // Grant required capabilities.
            $caps = [
                'moodle/user:create',
                'moodle/user:editprofile',
                'moodle/user:update',
                'moodle/user:viewdetails',
                'moodle/user:viewhiddendetails',
            ];
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $roleid, $systemcontext->id, true);
            }

            $role = $DB->get_record('role', ['id' => $roleid]);
        }

        // Assign the role to this user at system level (if not already).
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $role->id,
            'contextid' => $systemcontext->id,
            'userid' => $USER->id,
        ]);
        if (!$existing) {
            role_assign($role->id, $USER->id, $systemcontext->id, 'local_lernhive');
        }
    }

    /**
     * Ensure the current user can browse the simplified user list.
     *
     * Grants the 'local/lernhive:browseusers' capability via a lightweight
     * role 'lernhive_userbrowser' at system level.
     */
    private static function ensure_user_browse_capability(): void {
        global $DB, $USER;
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $systemcontext = \context_system::instance();

        // Already has capability? Nothing to do.
        if (has_capability('local/lernhive:browseusers', $systemcontext)) {
            return;
        }

        // Only grant to users who have a LernHive level record.
        $record = level_manager::get_level_record($USER->id);
        if (!$record) {
            return;
        }

        // Get or create the lightweight role.
        $role = $DB->get_record('role', ['shortname' => 'lernhive_userbrowser']);
        if (!$role) {
            $roleid = create_role(
                'LernHive User Browser',
                'lernhive_userbrowser',
                'Lightweight role for LernHive teachers to browse the user list',
                ''
            );
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);

            // Grant capabilities needed for viewing the user list.
            $caps = [
                'local/lernhive:browseusers',
                'moodle/user:viewdetails',
                'moodle/user:viewhiddendetails',
                'moodle/user:update',
                'moodle/user:delete',
            ];
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $roleid, $systemcontext->id, true);
            }

            $role = $DB->get_record('role', ['id' => $roleid]);
        }

        // Assign the role to this user at system level (if not already).
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $role->id,
            'contextid' => $systemcontext->id,
            'userid' => $USER->id,
        ]);
        if (!$existing) {
            role_assign($role->id, $USER->id, $systemcontext->id, 'local_lernhive');
        }
    }

    /**
     * Inject the LernHive sidebar on non-dashboard pages.
     *
     * Re-uses the same sidebar HTML and CSS classes from the dashboard,
     * but without the dashboard-specific content injection.
     */
    private static function inject_sidebar_global(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        global $USER, $PAGE;

        // Skip injection when the LernHive theme is active — it provides its
        // own <aside class="lernhive-sidebar"> with curated navitems. Injecting
        // a second <nav id="lernhive-sidebar"> would render an identical fixed
        // element on top with the wrong nav tree (flat_navigation items).
        if ($PAGE->theme->name === 'lernhive') {
            return;
        }

        $allowcourse = get_config('local_lernhive', 'allow_teacher_course_creation');
        $allowuser   = get_config('local_lernhive', 'allow_teacher_user_creation');
        $allowbrowse = get_config('local_lernhive', 'allow_teacher_user_browse');

        $messageurl = new \moodle_url('/message/index.php');
        $toursurl   = new \moodle_url('/local/lernhive_start/tours.php');
        $profileurl = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        $prefsurl   = new \moodle_url('/user/preferences.php');
        $langurl    = new \moodle_url('/user/language.php');

        // Lucide SVGs (same as dashboard).
        $svgdashboard = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
        $svgmessage = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>';
        $svglernpfade = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>';
        $svgbook = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>';
        $svgplus = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
        $svguserplus = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>';
        $svgprofile = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
        $svgsettings = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/></svg>';
        $svglang = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';

        // Build courses section.
        $courselistitems = '';
        $usercourses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname', 'fullname ASC');
        $cnt = 0;
        foreach ($usercourses as $c) {
            if ($cnt >= 10) break;
            $cnt++;
            $cname = s(format_string($c->fullname, true));
            $curl = (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(true);
            $courselistitems .= '<a class="lernhive-nav-item lernhive-nav-course" href="' . $curl . '">'
                . $svgbook . ' ' . $cname . '</a>';
        }
        $coursessection = '';
        if (!empty($courselistitems)) {
            $allcoursesurl = (new \moodle_url('/course/index.php'))->out(true);
            $coursessection = '<div class="lernhive-nav-section">'
                . '<div class="lernhive-nav-label">Kurse</div>'
                . $courselistitems
                . '<a class="lernhive-nav-item lernhive-nav-viewall" href="' . $allcoursesurl . '">'
                . 'Alle Kurse →</a></div>';
        }

        // Build system section.
        $systemitems = '';
        if ($allowcourse) {
            $courseurl = course_manager::get_create_course_url($USER->id);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $courseurl->out(true) . '">'
                . $svgplus . ' Kurs anlegen</a>';
        }
        if ($allowuser) {
            $userurl = new \moodle_url('/user/editadvanced.php', ['id' => -1]);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $userurl->out(true) . '">'
                . $svguserplus . ' Nutzer/in anlegen</a>';
        }
        if ($allowbrowse) {
            $svgusers = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
            $userlistnavurl = (new \moodle_url('/local/lernhive/users.php'))->out(true);
            $systemitems .= '<a class="lernhive-nav-item" href="' . $userlistnavurl . '">'
                . $svgusers . ' Nutzer/innen</a>';
        }
        $systemitems .= '<a class="lernhive-nav-item" href="' . $profileurl->out(true) . '">'
            . $svgprofile . ' Profil</a>';
        $systemitems .= '<a class="lernhive-nav-item" href="' . $prefsurl->out(true) . '">'
            . $svgsettings . ' Einstellungen</a>';

        // Admin link (only for site admins).
        if (is_siteadmin()) {
            $adminurl2 = (new \moodle_url('/admin/search.php'))->out(true);
            $svgadmin2 = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
            $systemitems .= '<a class="lernhive-nav-item" href="' . $adminurl2 . '">'
                . $svgadmin2 . ' Administration</a>';
        }

        // "Nutzer/innen" in main section.
        $usernav2 = '';
        if ($allowbrowse) {
            $svgusersnav2 = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
            $userlistnavurl3 = (new \moodle_url('/local/lernhive/users.php'))->out(true);
            $usernav2 = '<a class="lernhive-nav-item" href="' . $userlistnavurl3 . '">'
                . $svgusersnav2 . ' Nutzer/innen</a>';
        }

        $svglifebuoy2 = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>';
        $supporturl2 = (new \moodle_url('/local/lernhive/support.php'))->out(true);
        $sidebarhtml = '<nav class="lernhive-sidebar" id="lernhive-sidebar">'
            . '<div class="lernhive-nav-section">'
            . '<a class="lernhive-nav-item" href="' . $messageurl->out(true) . '">' . $svgmessage . ' Nachrichten</a>'
            . $usernav2
            . '<a class="lernhive-nav-item" href="' . $toursurl->out(true) . '">' . $svglernpfade . ' Onboarding</a>'
            . '<a class="lernhive-nav-item" href="' . $supporturl2 . '">' . $svglifebuoy2 . ' Support</a>'
            . '</div>'
            . $coursessection
            . '<div class="lernhive-nav-section">'
            . '<div class="lernhive-nav-label">System</div>'
            . $systemitems
            . '</div>'
            . '</nav>';
        $sidebarescaped = str_replace("'", "\\'", str_replace(["\r\n", "\n", "\r"], '', $sidebarhtml));

        $html = <<<HTML
<style>
/* LernHive global sidebar layout offset — NOT on dashboard (has its own sidebar) */
body:not(.pagelayout-mydashboard) #page.drawers,
body:not(.pagelayout-mydashboard) #page { margin-left: 240px !important; }
body:not(.pagelayout-mydashboard) .primary-navigation,
body:not(.pagelayout-mydashboard) nav.moremenu { display: none !important; }
body:not(.pagelayout-mydashboard) .drawer.drawer-left,
body:not(.pagelayout-mydashboard) #theme_boost-drawers-courseindex { display: none !important; }
/* Ensure navbar spans full width above the sidebar */
body .navbar.fixed-top, body .navbar { z-index: 100; }
/* Remove extra padding so content aligns flush with sidebar edge — NOT on dashboard */
body:not(.pagelayout-mydashboard) #page #page-content { padding-left: 0 !important; padding-right: 0 !important; }
body:not(.pagelayout-mydashboard) #page .main-inner { max-width: none !important; margin: 0 !important; padding: 0 24px !important; }
body:not(.pagelayout-mydashboard) #page #region-main { padding-left: 0 !important; padding-right: 0 !important; }
body:not(.pagelayout-mydashboard) #page #region-main > div { padding-left: 0 !important; padding-right: 0 !important; }
/* Hide breadcrumb on sidebar pages for Explorer */
body:not(.pagelayout-mydashboard) .breadcrumb { display: none !important; }
body:not(.pagelayout-mydashboard) nav[aria-label="Navigation"] { display: none !important; }
/* Hide page header if it duplicates sidebar nav */
body:not(.pagelayout-mydashboard) #page-header { padding: 8px 0 !important; }
@media (max-width: 768px) {
    body #page.drawers, body #page { margin-left: 0 !important; }
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Inject LernHive sidebar.
    if (!document.getElementById("lernhive-sidebar")) {
        var sidebarEl = document.createElement("div");
        sidebarEl.innerHTML = '{$sidebarescaped}';
        document.body.appendChild(sidebarEl.firstElementChild);
    }
    // Hide drawer toggle buttons.
    document.querySelectorAll("button[data-toggler='drawers']").forEach(function(btn) {
        var controls = btn.getAttribute("aria-controls") || "";
        if (controls.indexOf("courseindex") >= 0 || controls.indexOf("index") >= 0) {
            btn.style.display = "none";
        }
    });
});
</script>
HTML;
        $hook->add_html($html);
    }

    /**
     * Replace the user menu dropdown for Explorer level.
     *
     * Instead of a dropdown:
     * - Profile picture click → opens profile page directly.
     * - Logout becomes a separate icon in the navbar.
     * - Language selector becomes a separate globe icon in the navbar.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    private static function inject_usermenu_filter(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $USER, $PAGE;

        $profileurl = (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(true);
        $logouturl  = (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(true);
        $dashboardurl = (new \moodle_url('/my/'))->out(true);
        $courseindexurl = (new \moodle_url('/course/index.php'))->out(true);

        // Generate avatar URL server-side as fallback.
        $userpicture = new \user_picture($USER);
        $userpicture->size = 100;
        $avatarurl = $userpicture->get_url($PAGE)->out(false);
        $userinitials = mb_strtoupper(mb_substr($USER->firstname, 0, 1) . mb_substr($USER->lastname, 0, 1));

        // Page title for display after logo.
        $pagetitle = s($PAGE->heading ?? '');

        $html = <<<HTML
<style>
/* Profile link — no hover effect, just pointer */
.lernhive-profile-link { cursor: pointer !important; }
.lernhive-profile-link:hover { opacity: 1 !important; background: none !important; box-shadow: none !important; }
/* Logo hover — subtle grey background like language/logout icons */
.navbar .navbar-brand {
    display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 8px;
    transition: background 0.2s ease;
}
.navbar .navbar-brand:hover { background: #f3f5f8; text-decoration: none !important; }
/* Hide Edit Mode toggle on Dashboard for Explorer level */
body.pagelayout-mydashboard .editing-switch,
body.pagelayout-mydashboard [data-region="editmode"],
body.pagelayout-mydashboard .editmode-switch-form { display: none !important; }
/* Page title after logo */
.lh-page-title {
    font-size: 0.92rem; font-weight: 500; color: #5f6368;
    margin-left: 6px; white-space: nowrap; overflow: hidden;
    text-overflow: ellipsis; max-width: 300px;
}
.lh-page-title::before {
    content: '/'; margin-right: 8px; color: #dee2e6; font-weight: 400;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var usermenu = document.querySelector(".usermenu");
    if (!usermenu) return;

    // 1. Capture the avatar image BEFORE modifying the DOM.
    var avatarImg = usermenu.querySelector("img");
    var avatarClone = avatarImg ? avatarImg.cloneNode(true) : null;

    // 2. Completely strip Bootstrap dropdown from the usermenu container.
    usermenu.classList.remove("dropdown", "show");
    usermenu.querySelectorAll(".dropdown, .dropdown-toggle, .dropdown-menu, .show").forEach(function(el) {
        el.classList.remove("dropdown", "dropdown-toggle", "show");
        el.removeAttribute("data-bs-toggle");
        el.removeAttribute("data-toggle");
        el.removeAttribute("aria-expanded");
        el.removeAttribute("aria-haspopup");
    });
    // Remove the dropdown menu entirely.
    var dropdownMenu = usermenu.querySelector("[class*='dropdown-menu'], .usermenu-container .carousel");
    if (dropdownMenu) dropdownMenu.remove();

    // 3. Replace with a clean profile link — NO hover effect.
    var container = usermenu.querySelector(".usermenu-container") || usermenu;
    container.innerHTML = "";

    var profileLink = document.createElement("a");
    profileLink.href = "{$profileurl}";
    profileLink.className = "lernhive-profile-link";
    profileLink.title = "Profil";
    profileLink.style.cssText = "display:flex;align-items:center;border-radius:50%;padding:2px;cursor:pointer;text-decoration:none;";

    if (avatarClone) {
        avatarClone.style.cssText = "width:32px;height:32px;border-radius:50%;object-fit:cover;pointer-events:none;";
        profileLink.appendChild(avatarClone);
    } else {
        // Use server-side avatar URL or initials fallback.
        var fallbackImg = document.createElement("img");
        fallbackImg.src = "{$avatarurl}";
        fallbackImg.alt = "Profil";
        fallbackImg.style.cssText = "width:32px;height:32px;border-radius:50%;object-fit:cover;pointer-events:none;";
        fallbackImg.onerror = function() {
            // If image fails, show initials on branded gradient circle.
            var span = document.createElement("span");
            span.style.cssText = "display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#194866,#65a1b3);color:#fff;font-size:0.75rem;font-weight:600;letter-spacing:0.02em;user-select:none;";
            span.textContent = "{$userinitials}" || "?";
            this.replaceWith(span);
        };
        profileLink.appendChild(fallbackImg);
    }
    container.appendChild(profileLink);

    // 3. Intercept ALL clicks on the usermenu area to force profile navigation.
    // This runs in capture phase to beat Bootstrap's event listeners.
    usermenu.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        window.location.href = "{$profileurl}";
    }, true);

    // 4. Also block Bootstrap from re-initializing on the usermenu.
    // Set a MutationObserver to strip data-bs-toggle if it gets re-added.
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.type === "attributes" && m.attributeName === "data-bs-toggle") {
                m.target.removeAttribute("data-bs-toggle");
            }
        });
    });
    usermenu.querySelectorAll("*").forEach(function(el) {
        observer.observe(el, { attributes: true, attributeFilter: ["data-bs-toggle"] });
    });

    // 5. Replace the navbar-brand with LernHive logo + page title.
    var brand = document.querySelector(".navbar .navbar-brand");
    if (brand) {
        brand.innerHTML = '<span style="display:inline-flex;align-items:baseline;font-weight:800;font-size:1.15rem;letter-spacing:-0.02em;text-decoration:none;gap:0;"><span style="color:#194866;">Lern</span><span style="color:#f98012;">Hive</span></span>';
        brand.style.textDecoration = "none";
        brand.href = "/my/";

        // Add page title after brand.
        var pageTitle = "{$pagetitle}";
        if (pageTitle && pageTitle !== "Dashboard") {
            var titleEl = document.createElement("span");
            titleEl.className = "lh-page-title";
            titleEl.textContent = pageTitle;
            brand.parentNode.insertBefore(titleEl, brand.nextSibling);
        }
    }

    // 6. Build navbar icons.
    // Dashboard + Alle Kurse go LEFT (after page title).
    // Language + Logout go RIGHT (before profile).

    // Dashboard icon.
    var dashIcon = document.createElement("a");
    dashIcon.href = "{$dashboardurl}";
    dashIcon.className = "lernhive-navbar-icon";
    dashIcon.title = "Dashboard";
    dashIcon.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';

    // Alle Kurse icon (book).
    var coursesIcon = document.createElement("a");
    coursesIcon.href = "{$courseindexurl}";
    coursesIcon.className = "lernhive-navbar-icon";
    coursesIcon.title = "Alle Kurse";
    coursesIcon.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>';

    // Insert Dashboard + Alle Kurse LEFT (after page title or brand).
    var leftWrapper = document.createElement("div");
    leftWrapper.className = "lernhive-navbar-left-icons";
    leftWrapper.style.cssText = "display:flex;align-items:center;gap:2px;margin-left:8px;";
    leftWrapper.appendChild(dashIcon);
    leftWrapper.appendChild(coursesIcon);
    var pageTitle2 = document.querySelector(".lh-page-title");
    var insertAfterEl = pageTitle2 || document.querySelector(".navbar .navbar-brand");
    if (insertAfterEl && insertAfterEl.parentNode) {
        insertAfterEl.parentNode.insertBefore(leftWrapper, insertAfterEl.nextSibling);
    }

    // Language icon (globe).
    var langBtn = document.createElement("a");
    langBtn.className = "lernhive-navbar-icon";
    langBtn.title = "Sprache";
    langBtn.href = "#";
    langBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';

    var langPopup = document.createElement("div");
    langPopup.className = "lernhive-lang-popup";
    langPopup.id = "lernhive-lang-popup";
    langPopup.innerHTML = '<a href="?lang=de" class="lernhive-lang-option">Deutsch</a>'
        + '<a href="?lang=en" class="lernhive-lang-option">English</a>';

    langBtn.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        langPopup.classList.toggle("open");
    });
    document.addEventListener("click", function() {
        langPopup.classList.remove("open");
    });

    // Logout icon (log-out).
    var logoutBtn = document.createElement("a");
    logoutBtn.href = "{$logouturl}";
    logoutBtn.className = "lernhive-navbar-icon lernhive-navbar-logout";
    logoutBtn.title = "Abmelden";
    logoutBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';

    // Build right-side wrapper: Language (with popup) → Logout
    var wrapper = document.createElement("div");
    wrapper.className = "lernhive-navbar-actions";
    wrapper.style.cssText = "position:relative;display:flex;align-items:center;gap:2px;";
    wrapper.appendChild(langBtn);
    wrapper.appendChild(langPopup);
    wrapper.appendChild(logoutBtn);

    // Insert wrapper before the profile/usermenu (so order: ... → icons → profile)
    usermenu.parentNode.insertBefore(wrapper, usermenu);
});
</script>
HTML;
        $hook->add_html($html);
    }

    /**
     * Replace the course category listing on /course/index.php for Explorer level.
     *
     * Shows only the teacher's own courses (from their personal category)
     * in the same card layout as the dashboard.
     */
    private static function inject_course_index_filter(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $USER, $DB, $PAGE;

        // Get teacher's enrolled courses.
        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname, summary, category, startdate, enddate, visible', 'fullname ASC');

        // Build course cards HTML — same look as dashboard cards.
        $cardshtml = '';
        foreach ($courses as $c) {
            $cname = s(format_string($c->fullname, true));
            $curl  = (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(true);

            // Get category name.
            $catname = '';
            if (!empty($c->category)) {
                $cat = $DB->get_record('course_categories', ['id' => $c->category], 'name');
                if ($cat) {
                    $catname = s(format_string($cat->name, true));
                }
            }
            $cathtml = $catname ? '<div class="lh-ci-card-category">' . $catname . '</div>' : '';

            // Get course image.
            $context = \context_course::instance($c->id, IGNORE_MISSING);
            $imgurl = '';
            if ($context) {
                $course = new \core_course_list_element($c);
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    if ($isimage) {
                        $imgurl = \moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            null,
                            $file->get_filepath(),
                            $file->get_filename()
                        )->out(false);
                        break;
                    }
                }
            }

            // Gradient color fallback.
            $colors = ['#194866', '#65a1b3', '#f98012', '#3aadaa', '#669933', '#ab1d79'];
            $color  = $colors[$c->id % count($colors)];

            if ($imgurl) {
                $imgstyle = 'background:url(' . s($imgurl) . ') center/cover no-repeat;';
            } else {
                $imgstyle = 'background:linear-gradient(135deg, ' . $color . ', ' . $color . 'cc);';
            }

            $cardshtml .= '<a href="' . $curl . '" class="lh-ci-card">'
                . '<div class="lh-ci-card-img" style="' . $imgstyle . '"></div>'
                . '<div class="lh-ci-card-body">'
                . '<div class="lh-ci-card-name">' . $cname . '</div>'
                . $cathtml
                . '</div></a>';
        }

        if (empty($cardshtml)) {
            $cardshtml = '<p style="color:#80868b;text-align:center;padding:40px 0;">Noch keine Kurse vorhanden.</p>';
        }

        // Escape for JS.
        $cardsescaped = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $cardshtml);

        // Create course button.
        $createbtn = '';
        if (get_config('local_lernhive', 'allow_teacher_course_creation')) {
            $createurl = course_manager::get_create_course_url($USER->id);
            $createbtn = '<a href="' . $createurl->out(true) . '" class="lh-ci-btn">'
                . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
                . 'Kurs anlegen</a>';
        }
        $createbtnescaped = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $createbtn);

        $html = <<<HTML
<style>
/* LernHive: Course index — dashboard-style card grid */
.lh-ci-wrapper { max-width: 1100px; }
.lh-ci-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px;
}
.lh-ci-header h2 { font-size: 1.4rem; font-weight: 700; margin: 0; color: #1a1a1a; }
.lh-ci-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #194866; color: white; border: none;
    border-radius: 8px; padding: 8px 20px;
    font-size: 0.85rem; font-weight: 600; text-decoration: none;
    transition: all 0.2s; cursor: pointer;
}
.lh-ci-btn:hover { background: #0f2d40; box-shadow: 0 2px 8px rgba(0,0,0,0.15); color: white; text-decoration: none; }
.lh-ci-btn svg { vertical-align: -2px; }
.lh-ci-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
}
@media (max-width: 992px) { .lh-ci-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .lh-ci-grid { grid-template-columns: 1fr; } }
.lh-ci-card {
    display: flex; flex-direction: column;
    border-radius: 12px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    background: #fff; transition: all 0.2s;
    text-decoration: none; color: inherit;
}
.lh-ci-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-3px); text-decoration: none; color: inherit;
}
.lh-ci-card-img { height: 120px; width: 100%; }
.lh-ci-card-body { padding: 14px 16px; }
.lh-ci-card-name { font-size: 0.92rem; font-weight: 600; color: #1a1a1a; line-height: 1.35; }
.lh-ci-card-category { font-size: 0.78rem; color: #80868b; margin-top: 4px; }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var mainRole = document.querySelector("[role='main']");
    if (!mainRole) return;

    // Hide original Moodle content.
    mainRole.querySelectorAll(":scope > *").forEach(function(el) {
        if (!el.classList.contains("notifications")) el.style.display = "none";
    });

    // Hide page header + breadcrumb + primary nav.
    var ph = document.getElementById("page-header");
    if (ph) ph.style.display = "none";
    var bc = document.querySelector(".breadcrumb, ol.breadcrumb");
    if (bc) { var bcc = bc.closest("nav") || bc.parentElement; if (bcc) bcc.style.display = "none"; }
    var pn = document.querySelector(".primary-navigation, nav.moremenu");
    if (pn) pn.style.display = "none";
    // Hide secondary navigation on course index.
    var sn = document.querySelector(".secondary-navigation");
    if (sn) sn.style.display = "none";

    var wrapper = document.createElement("div");
    wrapper.className = "lh-ci-wrapper";
    wrapper.innerHTML = '<div class="lh-ci-header">'
        + '<h2>Meine Kurse</h2>'
        + '{$createbtnescaped}'
        + '</div>'
        + '<div class="lh-ci-grid">{$cardsescaped}</div>';
    mainRole.appendChild(wrapper);
});
</script>
HTML;
        $hook->add_html($html);
    }

    private static function inject_navbar_search(
        before_standard_top_of_body_html_generation $hook
    ): void {
        $searchurl = (new \moodle_url('/course/search.php'))->out(true);

        $html = <<<HTML
<script>
document.addEventListener("DOMContentLoaded", function() {
    // If there's already a search form in the navbar, skip.
    var navbar = document.querySelector(".navbar");
    if (!navbar) return;
    var existing = navbar.querySelector(".simplesearchform, [data-region='searchform'], .lernhive-navbar-search");
    if (existing) return;

    // Find the right-side area (usually the .d-flex with usermenu).
    var rightArea = navbar.querySelector(".navbar-nav.d-none.d-md-flex") || navbar.querySelector(".d-flex.align-items-center");
    if (!rightArea) return;

    // Create search form.
    var form = document.createElement("form");
    form.className = "lernhive-navbar-search";
    form.method = "get";
    form.action = "{$searchurl}";
    form.innerHTML = '<input type="text" name="search" placeholder="Suche..." autocomplete="off" />'
        + '<button type="submit" class="lernhive-search-btn">'
        + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
        + '</button>';

    rightArea.insertBefore(form, rightArea.firstChild);
});
</script>
HTML;
        $hook->add_html($html);
    }

    private static function inject_user_form_filter(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        // Don't simplify for site admins — they need the full form.
        if (is_siteadmin()) {
            return;
        }

        $html = <<<'HTML'
<style>
/* LernHive: Simplify user creation/edit form for Explorer level. */

/* Username field */
#fitem_id_username,
.fitem[data-groupname="username"],
#id_username {
    display: none !important;
}

/* Authentication method (Choose an authentication method) */
#fitem_id_auth,
.fitem[data-groupname="auth"] {
    display: none !important;
}

/* Force password change */
#fitem_id_preference_auth_forcepasswordchange,
.fitem[data-groupname="preference_auth_forcepasswordchange"] {
    display: none !important;
}

/* Log out of all web apps */
#fitem_id_signoutofotherservices,
.fitem[data-groupname="signoutofotherservices"] {
    display: none !important;
}

/* Additional names fieldset (firstnamephonetic, lastnamephonetic, middlename, alternatename) */
#id_additionalnames,
fieldset[id="id_additionalnames"] {
    display: none !important;
}

/* Interests fieldset */
#id_interests,
fieldset[id="id_interests"] {
    display: none !important;
}

/* Optional fieldset */
#id_optional,
fieldset[id="id_optional"] {
    display: none !important;
}

/* User picture: show the fieldset but hide the file picker repos
   (Content bank, Recent files, Server files, etc.) — only show file upload. */
#id_moodle_picture .fp-repo-area .fp-repo:not(:first-child),
#id_moodle_picture .fp-viewbar,
#id_moodle_picture .fp-navbar .fp-viewbar,
#id_moodle_picture [data-region="repository"] .fp-repo-area .fp-repo[data-repository="contentbank"],
#id_moodle_picture [data-region="repository"] .fp-repo-area .fp-repo[data-repository="recent"],
#id_moodle_picture [data-region="repository"] .fp-repo-area .fp-repo[data-repository="areafiles"] {
    display: none !important;
}

/* Hide description (profile description editor) for Explorer. */
#fitem_id_description_editor,
.fitem[data-groupname="description_editor"] {
    display: none !important;
}

/* Consistent collapse icons on fieldsets */
fieldset .ftoggler a,
fieldset .fheader {
    font-weight: 600 !important;
    font-size: 0.95rem !important;
    color: #1a1a1a !important;
    text-decoration: none !important;
}
fieldset .ftoggler a::before,
fieldset .fheader::before {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 28px !important; height: 28px !important;
    border-radius: 6px !important;
    background: #f3f5f8 !important;
    margin-right: 10px !important;
    font-size: 0.8rem !important;
    transition: background 0.15s, transform 0.2s !important;
}
fieldset .ftoggler a:hover::before,
fieldset .fheader:hover::before {
    background: #e0eaf2 !important;
}
fieldset.collapsed .ftoggler a::before,
fieldset.collapsed .fheader::before {
    transform: rotate(-90deg) !important;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Detect if this is a NEW user form (id=-1) vs editing existing user.
    var isNewUser = window.location.href.indexOf("id=-1") >= 0;

    var emailField = document.getElementById("id_email");
    var usernameField = document.getElementById("id_username");

    if (isNewUser && emailField && usernameField) {
        // --- Auto-set username = email (only for new users) ---
        emailField.addEventListener("input", function() {
            usernameField.value = emailField.value.toLowerCase().trim();
        });
        emailField.addEventListener("blur", function() {
            usernameField.value = emailField.value.toLowerCase().trim();
        });
        if (emailField.value) {
            usernameField.value = emailField.value.toLowerCase().trim();
        }

        // --- Auto-set auth = manual ---
        var authField = document.getElementById("id_auth");
        if (authField) authField.value = "manual";

        // --- Auto-check force password change ---
        var forcePassField = document.getElementById("id_preference_auth_forcepasswordchange");
        if (forcePassField) forcePassField.checked = true;

        // --- Safety net on submit ---
        var form = document.querySelector("form.mform");
        if (form) {
            form.addEventListener("submit", function() {
                if (usernameField && emailField) {
                    usernameField.value = emailField.value.toLowerCase().trim();
                }
                if (authField) authField.value = "manual";
                if (forcePassField) forcePassField.checked = true;
            });
        }
    }

    // --- Hide file picker repos (Content bank, Recent, etc.) in User Picture ---
    // These load dynamically, so use a MutationObserver.
    var picFieldset = document.getElementById("id_moodle_picture");
    if (picFieldset) {
        function hideRepos(root) {
            root.querySelectorAll(".fp-repo-area .fp-repo").forEach(function(repo) {
                var repoName = (repo.textContent || "").trim().toLowerCase();
                if (repoName.indexOf("upload") === -1 && repoName.indexOf("hochladen") === -1) {
                    repo.style.display = "none";
                }
            });
        }
        hideRepos(picFieldset);
        var observer = new MutationObserver(function() { hideRepos(picFieldset); });
        observer.observe(picFieldset, { childList: true, subtree: true });
    }
});
</script>
HTML;

        $hook->add_html($html);
    }

    /**
     * Redesign the user profile page to match the LernHive dashboard style.
     *
     * Replaces Moodle's default profile layout with a hero card, two-column
     * grid (personal data + courses on the left, level + onboarding + stats
     * on the right), matching the mockup-profile-v2.html design.
     */
    private static function inject_profile_redesign(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        global $USER, $DB, $PAGE;

        // Determine whose profile we're looking at.
        $profileuserid = optional_param('id', $USER->id, PARAM_INT);
        $isownprofile = ($profileuserid == $USER->id);

        // Load profile user data.
        $profileuser = $DB->get_record('user', ['id' => $profileuserid]);
        if (!$profileuser) {
            return;
        }

        $fullname = s(fullname($profileuser));
        $firstname = s($profileuser->firstname);
        $lastname = s($profileuser->lastname);
        $email = s($profileuser->email);
        $initials = mb_strtoupper(mb_substr($profileuser->firstname, 0, 1) . mb_substr($profileuser->lastname, 0, 1));

        // Role — get primary role name.
        $rolename = 'Nutzer/in';
        $rolesql = "SELECT r.shortname, r.name
                      FROM {role} r
                      JOIN {role_assignments} ra ON ra.roleid = r.id
                     WHERE ra.userid = ?
                  ORDER BY r.sortorder ASC
                     LIMIT 1";
        $role = $DB->get_record_sql($rolesql, [$profileuserid]);
        if ($role) {
            // Try localized role name.
            $localrolename = role_get_name($role);
            if ($localrolename) {
                $rolename = s($localrolename);
            }
        }

        // Profile picture URL.
        $userpicture = new \user_picture($profileuser);
        $userpicture->size = 200;
        $avatarurl = $userpicture->get_url($PAGE)->out(false);
        $hasavatar = !empty($profileuser->picture);

        // Avatar HTML: image or initials fallback.
        if ($hasavatar) {
            $avatarhtml = '<img src="' . s($avatarurl) . '" alt="' . $fullname . '" style="width:120px;height:120px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,0.1);">';
        } else {
            $avatarhtml = '<div class="lh-profile-avatar-initials">' . $initials . '</div>';
        }

        // City/Country.
        $location = '';
        if (!empty($profileuser->city)) {
            $location = s($profileuser->city);
            if (!empty($profileuser->country)) {
                $countries = get_string_manager()->get_list_of_countries();
                if (isset($countries[$profileuser->country])) {
                    $location .= ', ' . s($countries[$profileuser->country]);
                }
            }
        }

        // Member since.
        $membersince = userdate($profileuser->timecreated, '%B %Y');

        // Language.
        $lang = !empty($profileuser->lang) ? $profileuser->lang : 'de';
        $langnames = ['de' => 'Deutsch (de)', 'en' => 'English (en)', 'fr' => 'Français (fr)', 'es' => 'Español (es)'];
        $langdisplay = $langnames[$lang] ?? $lang;

        // Edit profile URL.
        $editurl = (new \moodle_url('/user/editadvanced.php', ['id' => $profileuserid]))->out(true);

        // ── Courses ──
        $courses = enrol_get_users_courses($profileuserid, true, 'id, fullname, category', 'fullname ASC');
        $coursecount = count($courses);
        $courseshtml = '';
        $colors = ['#194866', '#65a1b3', '#f98012', '#3aadaa', '#669933', '#ab1d79'];
        foreach ($courses as $c) {
            $cname = s(format_string($c->fullname, true));
            $curl = (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(true);
            $color = $colors[$c->id % count($colors)];
            $catname = '';
            if (!empty($c->category)) {
                $cat = $DB->get_record('course_categories', ['id' => $c->category], 'name');
                if ($cat) {
                    $catname = s(format_string($cat->name, true));
                }
            }
            $courseshtml .= '<a href="' . $curl . '" class="lh-prof-course-item">'
                . '<span class="lh-prof-course-dot" style="background:' . $color . ';"></span>'
                . '<span class="lh-prof-course-name">' . $cname . '</span>'
                . ($catname ? '<span class="lh-prof-course-cat">' . $catname . '</span>' : '')
                . '</a>';
        }
        if (empty($courseshtml)) {
            $courseshtml = '<p style="color:#80868b;font-size:0.85rem;padding:12px 0;">Noch keine Kurse.</p>';
        }

        // ── Student count ──
        $studentcount = 0;
        $teachercourses = $DB->get_fieldset_sql(
            "SELECT DISTINCT e.courseid FROM {enrol} e JOIN {user_enrolments} ue ON ue.enrolid = e.id WHERE ue.userid = ?",
            [$profileuserid]
        );
        if (!empty($teachercourses)) {
            list($insql, $params) = $DB->get_in_or_equal($teachercourses);
            $studentcount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e JOIN {user_enrolments} ue ON ue.enrolid = e.id WHERE e.courseid {$insql} AND ue.userid != ?",
                array_merge($params, [$profileuserid])
            );
        }

        // ── Level data ──
        $userlevel = level_manager::get_level($profileuserid);
        $levelname = level_manager::get_level_name($userlevel);
        $levelnames = [1 => 'Explorer', 2 => 'Creator', 3 => 'Pro', 4 => 'Expert', 5 => 'Master'];

        $levelstepshtml = '';
        for ($i = 1; $i <= 5; $i++) {
            $circleclass = '';
            if ($i == $userlevel) {
                $circleclass = 'active';
            } elseif ($i < $userlevel) {
                $circleclass = 'done';
            }
            $labelclass = ($i == $userlevel) ? 'active' : '';
            $lname = $levelnames[$i];
            // Check icon for done levels.
            $circleContent = ($i < $userlevel)
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
                : $i;
            $levelstepshtml .= '<div class="lh-prof-level-step">'
                . '<div class="lh-prof-level-circle ' . $circleclass . '">' . $circleContent . '</div>'
                . '<div class="lh-prof-level-label ' . $labelclass . '">' . $lname . '</div>'
                . '</div>';
        }

        // Level info message.
        $levelinfo = '';
        if ($userlevel < 5) {
            $nextlevel = $levelnames[$userlevel + 1] ?? '';
            $levelinfo = 'Du bist <strong>' . s($levelname) . '</strong> — schließe die Onboarding-Touren ab, um <strong>' . s($nextlevel) . '</strong> freizuschalten.';
        } else {
            $levelinfo = 'Du hast das höchste Level <strong>Master</strong> erreicht!';
        }

        // ── Tour progress ──
        $tourpct = 0;
        $tourtotal = 0;
        $tourcompleted = 0;
        $onboardinghtml = '';
        $startplugininstalled = $DB->get_manager()->table_exists('local_lernhive_start_cats');
        if ($startplugininstalled && class_exists('\\local_lernhive_start\\tour_manager')) {
            $tourprogress = \local_lernhive_start\tour_manager::get_level_progress($userlevel, $profileuserid);
            if ($tourprogress) {
                $tourpct = $tourprogress['percent'];
                $tourtotal = $tourprogress['total_tours'];
                $tourcompleted = $tourprogress['completed_tours'];

                $catcolors = ['#194866', '#65a1b3', '#f98012', '#3aadaa', '#669933', '#ab1d79'];
                $lucideicons = [
                    'user-plus' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
                    'users' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                    'book-plus' => '<svg viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><line x1="12" y1="8" x2="12" y2="14"/><line x1="9" y1="11" x2="15" y2="11"/></svg>',
                    'settings' => '<svg viewBox="0 0 24 24"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
                    'plus-square' => '<svg viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
                    'message-circle' => '<svg viewBox="0 0 24 24"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>',
                ];

                $profmodalshtml = '';
                if (!empty($tourprogress['categories'])) {
                    $ci = 0;
                    $sesskey = sesskey();
                    foreach ($tourprogress['categories'] as $cat) {
                        $strmanager = get_string_manager();
                        if ($strmanager->string_exists($cat->name, 'local_lernhive_start')) {
                            $catname = s(get_string($cat->name, 'local_lernhive_start'));
                        } else {
                            $catname = s($cat->name);
                        }
                        // Also get description.
                        $desckey = $cat->name . '_desc';
                        $catdesc = '';
                        if ($strmanager->string_exists($desckey, 'local_lernhive_start')) {
                            $catdesc = s(get_string($desckey, 'local_lernhive_start'));
                        }

                        $catcolor = s($cat->color);
                        $caticon = $lucideicons[$cat->icon] ?? '';
                        $pct = $cat->progress['percent'];
                        $progresstext = $cat->progress['completed'] . '/' . $cat->progress['total'];
                        $catid = (int) $cat->id;

                        // Clickable item opens modal.
                        $onboardinghtml .= '<div class="lh-prof-onboard-item lh-prof-onboard-link" onclick="document.getElementById(\'lh-prof-modal-' . $catid . '\').classList.add(\'open\')" style="cursor:pointer;">'
                            . '<div class="lh-prof-onboard-icon" style="background:' . $catcolor . '22;color:' . $catcolor . ';">' . $caticon . '</div>'
                            . '<div class="lh-prof-onboard-info">'
                            . '<div class="lh-prof-onboard-name">' . $catname . '</div>'
                            . '<div class="lh-prof-onboard-bar"><div class="lh-prof-onboard-bar-fill" style="width:' . $pct . '%;background:' . $catcolor . ';"></div></div>'
                            . '</div>'
                            . '<span class="lh-prof-onboard-pct">' . $progresstext . '</span>'
                            . '<svg class="lh-prof-onboard-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>'
                            . '</div>';

                        // Build modal: list of tours in this category.
                        $tourlisthtml = '';
                        foreach ($cat->progress['tours'] as $tour) {
                            $tourid = (int) $tour->tourid;
                            $tourrecord = $DB->get_record('tool_usertours_tours', ['id' => $tourid], 'name');
                            $tourname = $tourrecord ? s($tourrecord->name) : "Tour {$tourid}";
                            $tourname = preg_replace('/^LernHive:\s*/i', '', $tourname);
                            $starturl = (new \moodle_url('/local/lernhive_start/starttour.php', [
                                'tourid' => $tourid,
                                'sesskey' => $sesskey,
                            ]))->out(true);
                            $completedclass = $tour->completed ? 'completed' : '';
                            $checkicon = $tour->completed
                                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>';
                            $btnlabel = $tour->completed ? 'Wiederholen' : 'Starten';

                            $tourlisthtml .= '<a href="' . $starturl . '" class="lernhive-modal-tour ' . $completedclass . '">'
                                . '<span class="lernhive-modal-tour-icon">' . $checkicon . '</span>'
                                . '<span class="lernhive-modal-tour-name">' . $tourname . '</span>'
                                . '<span class="lernhive-modal-tour-btn">' . $btnlabel . ' →</span>'
                                . '</a>';
                        }

                        $profmodalshtml .= '<div class="lernhive-modal-overlay" id="lh-prof-modal-' . $catid . '" onclick="if(event.target===this)this.classList.remove(\'open\')">'
                            . '<div class="lernhive-modal">'
                            . '<div class="lernhive-modal-header" style="color:' . $catcolor . ';">'
                            . '<div class="lernhive-modal-header-icon" style="background:' . $catcolor . '22;color:' . $catcolor . ';">' . $caticon . '</div>'
                            . '<h3>' . $catname . '</h3>'
                            . '<button class="lernhive-modal-close" onclick="this.closest(\'.lernhive-modal-overlay\').classList.remove(\'open\')">'
                            . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                            . '</button>'
                            . '</div>'
                            . ($catdesc ? '<div class="lernhive-modal-desc">' . $catdesc . '</div>' : '')
                            . '<div class="lernhive-modal-progress">'
                            . '<div class="lernhive-modal-progress-bar">'
                            . '<div class="lernhive-modal-progress-fill" style="width:' . $pct . '%;background:' . $catcolor . ';"></div>'
                            . '</div>'
                            . '<span class="lernhive-modal-progress-text">' . $progresstext . ' abgeschlossen</span>'
                            . '</div>'
                            . '<div class="lernhive-modal-tours">' . $tourlisthtml . '</div>'
                            . '</div>'
                            . '</div>';

                        $ci++;
                    }
                }
            }
        }

        $toursurl = (new \moodle_url('/local/lernhive_start/tours.php'))->out(true);
        $courselisturl = (new \moodle_url('/course/index.php'))->out(true);
        $usermanageurl = (new \moodle_url('/admin/user.php'))->out(true);

        // ── Last access ──
        $lastlogin = $profileuser->lastaccess
            ? userdate($profileuser->lastaccess, get_string('strftimedatetime', 'langconfig'))
            : get_string('never');
        $firstaccess = $profileuser->firstaccess
            ? userdate($profileuser->firstaccess, '%d. %B %Y')
            : '-';

        // ── Location HTML ──
        $locationhtml = '';
        if ($location) {
            $locationhtml = '<div class="lh-prof-meta-item">'
                . '<svg viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'
                . $location . '</div>';
        }

        // ── Edit button (only for own profile or admin) ──
        $editbtnhtml = '';
        if ($isownprofile || is_siteadmin()) {
            $editbtnhtml = '<div class="lh-prof-actions">'
                . '<a href="' . $editurl . '" class="lh-prof-btn lh-prof-btn-primary">'
                . '<svg viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'
                . 'Profil bearbeiten</a></div>';
        }

        // Escape all dynamic HTML for JS injection.
        $escapedCoursesHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $courseshtml);
        $escapedOnboardingHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $onboardinghtml);
        $escapedModalsHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $profmodalshtml ?? '');
        $escapedLevelStepsHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $levelstepshtml);
        $escapedAvatarHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $avatarhtml);
        $escapedLocationHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $locationhtml);
        $escapedEditBtnHtml = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $editbtnhtml);
        $escapedLevelInfo = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $levelinfo);

        $html = <<<HTML
<style>
/* === LernHive Profile Redesign (Explorer level) === */
.lh-prof-wrapper {
    max-width: 1100px;
    margin: 0;
    padding-top: 16px;
}

/* Hero Card */
.lh-prof-hero {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid #e9ecef;
}
.lh-prof-avatar-initials {
    width: 120px; height: 120px; border-radius: 50%;
    background: linear-gradient(135deg, #194866, #65a1b3);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 2.5rem; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.lh-prof-info { flex: 1; min-width: 0; }
.lh-prof-info h1 {
    font-size: 1.6rem; font-weight: 700; margin: 0 0 6px 0; color: #1a1a1a;
}
.lh-prof-role {
    display: inline-block; background: #e0eaf2; color: #194866;
    padding: 4px 14px; border-radius: 50px;
    font-size: 0.8rem; font-weight: 600; margin-bottom: 14px;
}
.lh-prof-meta { display: flex; flex-direction: column; gap: 6px; }
.lh-prof-meta-item {
    display: flex; align-items: center; gap: 8px;
    color: #5f6368; font-size: 0.88rem;
}
.lh-prof-meta-item svg {
    width: 16px; height: 16px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    flex-shrink: 0; color: #80868b;
}
.lh-prof-actions {
    display: flex; gap: 10px; align-self: flex-start; flex-shrink: 0;
}
.lh-prof-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 9px 20px; border: none; border-radius: 8px;
    font-size: 0.85rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all 0.2s ease; text-decoration: none;
}
.lh-prof-btn svg {
    width: 16px; height: 16px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}
.lh-prof-btn-primary { background: #194866; color: white; }
.lh-prof-btn-primary:hover { background: #0f2d40; box-shadow: 0 2px 8px rgba(0,0,0,0.15); color: white; text-decoration: none; }

/* Two-column grid */
.lh-prof-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .lh-prof-grid { grid-template-columns: 1fr; }
    .lh-prof-hero { flex-direction: column; align-items: center; text-align: center; }
    .lh-prof-meta { align-items: center; }
    .lh-prof-actions { align-self: center; }
}

/* Section Card */
.lh-prof-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    padding: 20px 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    margin-bottom: 16px;
}
.lh-prof-card:last-child { margin-bottom: 0; }
.lh-prof-card-title {
    font-size: 0.9rem; font-weight: 700; color: #1a1a1a;
    padding-bottom: 10px; border-bottom: 1px solid #e9ecef;
    margin-bottom: 12px;
    text-transform: uppercase; letter-spacing: 0.02em;
}

/* Detail Rows */
.lh-prof-detail-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid #e9ecef;
}
.lh-prof-detail-row:last-child { border-bottom: none; }
.lh-prof-detail-label { color: #5f6368; font-size: 0.85rem; font-weight: 500; }
.lh-prof-detail-value { font-weight: 600; font-size: 0.9rem; color: #1a1a1a; display: flex; align-items: center; }
.lh-prof-detail-value.accent { color: #194866; }

/* Course List */
.lh-prof-course-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid #e9ecef;
    text-decoration: none; color: inherit; transition: all 0.2s;
}
.lh-prof-course-item:last-child { border-bottom: none; }
.lh-prof-course-item:hover { color: #194866; }
.lh-prof-course-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.lh-prof-course-name { font-size: 0.88rem; font-weight: 600; }
.lh-prof-course-cat { font-size: 0.75rem; color: #80868b; margin-left: auto; }

/* Level Track */
.lh-prof-level-track-wrapper { position: relative; margin: 12px 0 12px; }
.lh-prof-level-connector {
    position: absolute; top: 24px; left: 10%; right: 10%; height: 3px;
    background: #e9ecef; z-index: 0;
}
.lh-prof-level-track {
    display: flex; align-items: center; gap: 0;
    position: relative; z-index: 1;
}
.lh-prof-level-step {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.lh-prof-level-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: #e9ecef; display: flex;
    align-items: center; justify-content: center;
    color: #80868b; font-weight: 700; font-size: 1rem;
    transition: all 0.2s; border: 3px solid transparent;
}
.lh-prof-level-circle.active {
    background: #194866; color: white; border-color: #e0eaf2;
    box-shadow: 0 0 0 4px #e0eaf2;
}
.lh-prof-level-circle.done { background: #e0f5f4; color: #3aadaa; border-color: #e0f5f4; }
.lh-prof-level-circle.done svg { stroke: #3aadaa; }
.lh-prof-level-label { font-size: 0.72rem; font-weight: 600; color: #80868b; text-align: center; }
.lh-prof-level-label.active { color: #194866; }

/* Level info box */
.lh-prof-level-info {
    background: #e0eaf2; padding: 12px 16px; border-radius: 8px;
    margin-top: 16px; display: flex; align-items: center; gap: 10px;
    font-size: 0.85rem; color: #1a1a1a;
}
.lh-prof-level-info strong { color: #194866; font-weight: 700; }
.lh-prof-level-info svg {
    width: 18px; height: 18px; flex-shrink: 0;
    stroke: #194866; fill: none; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
}

/* Onboarding items */
.lh-prof-onboard-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 8px; border-bottom: 1px solid #e9ecef;
}
.lh-prof-onboard-item:last-child { border-bottom: none; }
.lh-prof-onboard-link {
    text-decoration: none; color: inherit; border-radius: 8px;
    margin: 0 -8px; transition: background 0.15s ease;
    cursor: pointer;
}
.lh-prof-onboard-link:hover {
    background: #f5f8fa; text-decoration: none; color: inherit;
}
.lh-prof-onboard-link:hover .lh-prof-onboard-name { color: #194866; }
.lh-prof-onboard-link:hover .lh-prof-onboard-arrow { opacity: 1; color: #194866; }
.lh-prof-onboard-arrow {
    width: 18px; height: 18px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    flex-shrink: 0; opacity: 0.3; transition: opacity 0.15s ease;
    color: #80868b;
}
.lh-prof-onboard-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.lh-prof-onboard-icon svg {
    width: 18px; height: 18px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}
.lh-prof-onboard-info { flex: 1; min-width: 0; }
.lh-prof-onboard-name { font-size: 0.85rem; font-weight: 600; color: #1a1a1a; transition: color 0.15s ease; }
.lh-prof-onboard-bar {
    height: 4px; background: #e9ecef; border-radius: 2px;
    margin-top: 4px; overflow: hidden;
}
.lh-prof-onboard-bar-fill { height: 100%; border-radius: 2px; }
.lh-prof-onboard-pct { font-size: 0.75rem; color: #80868b; font-weight: 600; flex-shrink: 0; }

/* Übersicht links */
.lh-prof-detail-link {
    text-decoration: none; color: inherit; border-radius: 6px;
    padding: 10px 8px !important; margin: 0 -8px;
    transition: background 0.15s ease; cursor: pointer;
}
.lh-prof-detail-link:hover {
    background: #f5f8fa; text-decoration: none; color: inherit;
}
.lh-prof-detail-link:hover .lh-prof-link-arrow { opacity: 1; }
.lh-prof-detail-link:hover .lh-prof-detail-label { color: #194866; }
.lh-prof-link-arrow {
    width: 16px; height: 16px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    opacity: 0.3; transition: opacity 0.15s ease;
    margin-left: 6px; vertical-align: middle; display: inline-block;
}

/* Login activity */
.lh-prof-login-info {
    font-size: 0.82rem; color: #80868b; padding-top: 8px;
    display: flex; flex-direction: column; gap: 4px;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var mainRole = document.querySelector("[role='main']");
    if (!mainRole) return;

    // Hide the original Moodle profile content.
    mainRole.querySelectorAll(":scope > *").forEach(function(el) {
        if (!el.classList.contains("notifications")) el.style.display = "none";
    });

    // Hide the original page header (Moodle profile heading).
    var pageHeader = document.getElementById("page-header");
    if (pageHeader) pageHeader.style.display = "none";

    // Hide breadcrumb.
    var breadcrumb = document.querySelector("nav[aria-label] ol.breadcrumb, .breadcrumb");
    if (breadcrumb) {
        var bc = breadcrumb.closest("nav") || breadcrumb.parentElement;
        if (bc) bc.style.display = "none";
    }

    // Hide primary nav on profile.
    var pn = document.querySelector(".primary-navigation, nav.moremenu");
    if (pn) pn.style.display = "none";

    // Hide secondary navigation on profile.
    var sn = document.querySelector(".secondary-navigation");
    if (sn) sn.style.display = "none";

    // Build the new profile layout.
    var wrapper = document.createElement("div");
    wrapper.className = "lh-prof-wrapper";
    wrapper.innerHTML = ''
        // ── Hero Card ──
        + '<div class="lh-prof-hero">'
        +   '{$escapedAvatarHtml}'
        +   '<div class="lh-prof-info">'
        +     '<h1>{$fullname}</h1>'
        +     '<div class="lh-prof-role">{$rolename}</div>'
        +     '<div class="lh-prof-meta">'
        +       '<div class="lh-prof-meta-item"><svg viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>{$email}</div>'
        +       '{$escapedLocationHtml}'
        +       '<div class="lh-prof-meta-item"><svg viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Mitglied seit {$membersince}</div>'
        +     '</div>'
        +   '</div>'
        +   '{$escapedEditBtnHtml}'
        + '</div>'

        // ── Two-column Grid ──
        + '<div class="lh-prof-grid">'

        // Left column: Personal Data + Courses
        +   '<div>'
        +     '<div class="lh-prof-card">'
        +       '<div class="lh-prof-card-title">Persönliche Daten</div>'
        +       '<div class="lh-prof-detail-row"><span class="lh-prof-detail-label">Vorname</span><span class="lh-prof-detail-value">{$firstname}</span></div>'
        +       '<div class="lh-prof-detail-row"><span class="lh-prof-detail-label">Nachname</span><span class="lh-prof-detail-value">{$lastname}</span></div>'
        +       '<div class="lh-prof-detail-row"><span class="lh-prof-detail-label">E-Mail</span><span class="lh-prof-detail-value accent">{$email}</span></div>'
        +       '<div class="lh-prof-detail-row"><span class="lh-prof-detail-label">Rolle</span><span class="lh-prof-detail-value">{$rolename}</span></div>'
        +       '<div class="lh-prof-detail-row"><span class="lh-prof-detail-label">Sprache</span><span class="lh-prof-detail-value">{$langdisplay}</span></div>'
        +     '</div>'
        +     '<div class="lh-prof-card">'
        +       '<div class="lh-prof-card-title">Meine Kurse</div>'
        +       '{$escapedCoursesHtml}'
        +     '</div>'
        +   '</div>'

        // Right column: Level + Onboarding + Stats
        +   '<div>'
        +     '<div class="lh-prof-card">'
        +       '<div class="lh-prof-card-title">LernHive Level</div>'
        +       '<div class="lh-prof-level-track-wrapper">'
        +         '<div class="lh-prof-level-connector"></div>'
        +         '<div class="lh-prof-level-track">{$escapedLevelStepsHtml}</div>'
        +       '</div>'
        +       '<div class="lh-prof-level-info">'
        +         '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        +         '<span>{$escapedLevelInfo}</span>'
        +       '</div>'
        +     '</div>'
        +     '<div class="lh-prof-card">'
        +       '<div class="lh-prof-card-title">Onboarding-Fortschritt</div>'
        +       '{$escapedOnboardingHtml}'
        +       '<div style="text-align:center;padding-top:12px;"><a href="{$toursurl}" style="font-size:0.85rem;color:#194866;text-decoration:none;font-weight:500;">Alle Touren anzeigen →</a></div>'
        +     '</div>'
        +     '<div class="lh-prof-card">'
        +       '<div class="lh-prof-card-title">Übersicht</div>'
        +       '<a href="{$courselisturl}" class="lh-prof-detail-row lh-prof-detail-link"><span class="lh-prof-detail-label">Kurse</span><span class="lh-prof-detail-value accent">{$coursecount}<svg class="lh-prof-link-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></span></a>'
        +       '<a href="{$usermanageurl}" class="lh-prof-detail-row lh-prof-detail-link"><span class="lh-prof-detail-label">Teilnehmer/innen</span><span class="lh-prof-detail-value accent">{$studentcount}<svg class="lh-prof-link-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></span></a>'
        +       '<a href="{$toursurl}" class="lh-prof-detail-row lh-prof-detail-link"><span class="lh-prof-detail-label">Onboarding</span><span class="lh-prof-detail-value" style="color:#f98012;">{$tourpct}%<svg class="lh-prof-link-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></span></a>'
        +       '<div class="lh-prof-login-info">'
        +         '<span>Letzter Login: {$lastlogin}</span>'
        +         '<span>Erster Zugriff: {$firstaccess}</span>'
        +       '</div>'
        +     '</div>'
        +   '</div>'

        + '</div>'; // end grid

    mainRole.appendChild(wrapper);

    // Inject onboarding tour modals.
    var modalsContainer = document.createElement("div");
    modalsContainer.innerHTML = '{$escapedModalsHtml}';
    document.body.appendChild(modalsContainer);
});
</script>
HTML;
        $hook->add_html($html);
    }

    /**
     * Add Course Index icon link to the navbar on course pages.
     *
     * On course/view pages there is no sidebar, so we add a small icon button
     * for course overview. Dashboard icon is already global (inject_usermenu_filter).
     * Also hides the Moodle "Dashboard" text link from the secondary nav.
     */
    private static function inject_course_navbar_icons(
        before_standard_top_of_body_html_generation $hook
    ): void {
        $courseindexurl = (new \moodle_url('/course/index.php'))->out(true);

        $html = <<<HTML
<style>
/* Course page: Kursindex icon in navbar */
.lh-course-nav-icons {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-right: 8px;
}
.lh-course-nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: #5f6368;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}
.lh-course-nav-icon:hover {
    background: #f5f6f8;
    color: #194866;
    text-decoration: none;
}
.lh-course-nav-icon svg {
    width: 20px;
    height: 20px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
/* Hide Moodle's "Dashboard" text link in secondary nav on course pages */
.secondary-navigation a[href*="/my/"],
.secondary-navigation li a[data-key="myhome"],
.moremenu a[href*="/my/"] {
    display: none !important;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var navbar = document.querySelector(".navbar");
    if (!navbar) return;

    // Don't add if already present.
    if (document.querySelector(".lh-course-nav-icons")) return;

    // Create the icon container — only Course Index icon.
    var icons = document.createElement("div");
    icons.className = "lh-course-nav-icons";
    icons.innerHTML = ''
        + '<a href="{$courseindexurl}" class="lh-course-nav-icon" title="Meine Kurse">'
        +   '<svg viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>'
        + '</a>';

    // Insert after the navbar-brand + page title.
    var pageTitle = navbar.querySelector(".lh-page-title");
    var insertAfter = pageTitle || navbar.querySelector(".navbar-brand");
    if (insertAfter) {
        insertAfter.parentNode.insertBefore(icons, insertAfter.nextSibling);
    } else {
        navbar.insertBefore(icons, navbar.firstChild);
    }

    // Also hide any "Dashboard" text link in the page-level nav.
    document.querySelectorAll(".secondary-navigation a, .moremenu a").forEach(function(a) {
        if (a.textContent.trim() === "Dashboard" || (a.href && a.href.indexOf("/my/") >= 0)) {
            a.style.display = "none";
        }
    });
});
</script>
HTML;
        $hook->add_html($html);
    }

    /**
     * Lightweight course view tweaks for Explorer level.
     *
     * - Hides breadcrumb, primary nav, secondary nav, page header.
     * - Keeps Moodle's native course index drawer (user can toggle it).
     * - No custom sidebar on course pages — course index can be long.
     * - Injects an airy course heading above the content.
     */
    /**
     * Inject action buttons (Nutzer/innen + Kurseinstellungen) on course/edit.php.
     * Mirrors the heading style of the course view page.
     */
    private static function inject_course_edit_heading(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        global $COURSE, $DB;

        $courseid = $COURSE->id;
        if ($courseid <= 1) {
            return;
        }

        $coursename = s(format_string($COURSE->fullname, true));
        $courseurl = (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(true);
        $enrolurl = (new \moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]))->out(true);
        $settingsurl = (new \moodle_url('/course/edit.php', ['id' => $courseid]))->out(true);

        // Category name (same as course view).
        $catname = '';
        if (!empty($COURSE->category)) {
            $cat = $DB->get_record('course_categories', ['id' => $COURSE->category], 'name');
            if ($cat) {
                $catname = s(format_string($cat->name, true));
            }
        }

        // Participants count.
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        $participantcount = 0;
        if ($context) {
            $participantcount = count_enrolled_users($context);
        }

        // Meta info (identical to course view).
        $metahtml = '';
        if ($catname) {
            $metahtml .= '<span class="lh-cv-meta-item"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>' . $catname . '</span>';
            $metahtml .= '<span class="lh-cv-meta-div">&middot;</span>';
        }
        $metahtml .= '<span class="lh-cv-meta-item"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' . $participantcount . ' Teilnehmer/innen</span>';
        $metaescaped = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $metahtml);

        $html = <<<HTML
<style>
/* === LernHive Course Edit — identical to course view === */
/* Moodle 5.x uses body.path-course + body#page-course-edit for course/edit.php */
body#page-course-edit .secondary-navigation { display: none !important; }
body#page-course-edit .breadcrumb { display: none !important; }
body#page-course-edit #page-header { display: none !important; }
body#page-course-edit #region-main > h2:first-of-type,
body#page-course-edit [role="main"] > h2:first-of-type { display: none !important; }
/* Heading + button styles (also defined in course view, duplicated here for edit) */
body#page-course-edit .lh-cv-heading { margin-bottom: 20px; padding-top: 12px; }
body#page-course-edit .lh-cv-heading-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
body#page-course-edit .lh-cv-heading h1 { font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0 0 6px 0; line-height: 1.3; }
body#page-course-edit .lh-cv-meta { display: flex; align-items: center; gap: 12px; color: #5f6368; font-size: 0.85rem; }
body#page-course-edit .lh-cv-meta-item { display: flex; align-items: center; gap: 5px; }
body#page-course-edit .lh-cv-meta-item svg { color: #80868b; }
body#page-course-edit .lh-cv-meta-div { color: #dee2e6; }
body#page-course-edit .lh-cv-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
body#page-course-edit .lh-cv-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s ease; white-space: nowrap;
    border: 1px solid #dee2e6; background: #fff; color: #5f6368;
}
body#page-course-edit .lh-cv-action-btn svg {
    width: 16px; height: 16px; fill: none; stroke: currentColor;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0;
}
body#page-course-edit .lh-cv-action-btn:hover { background: #f3f5f8; color: #194866; border-color: #c8cdd2; text-decoration: none; }
body#page-course-edit .lh-cv-action-active { background: #194866 !important; color: #fff !important; border-color: #194866 !important; }
body#page-course-edit .lh-cv-action-active svg { stroke: #fff; }
body#page-course-edit .lh-cv-action-active:hover { background: #0f2e3f !important; }
/* Content area clean-up */
body#page-course-edit #page .main-inner { max-width: none !important; padding: 0 24px !important; }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var mainRole = document.querySelector("[role='main']");
    if (!mainRole) return;

    // Hide Moodle's "Edit course settings" heading.
    var moodleH2 = mainRole.querySelector("h2");
    if (moodleH2) moodleH2.style.display = "none";

    var heading = document.createElement("div");
    heading.className = "lh-cv-heading";
    heading.innerHTML = '<div class="lh-cv-heading-top">'
        + '<div>'
        +   '<h1>{$coursename}</h1>'
        +   '<div class="lh-cv-meta">{$metaescaped}</div>'
        + '</div>'
        + '<div class="lh-cv-actions">'
        +   '<a href="{$enrolurl}" class="lh-cv-action-btn" title="Nutzer/innen verwalten">'
        +     '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
        +     'Nutzer/innen'
        +   '</a>'
        +   '<a href="{$settingsurl}" class="lh-cv-action-btn lh-cv-action-active" title="Kurseinstellungen">'
        +     '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>'
        +     'Einstellungen'
        +   '</a>'
        + '</div>'
        + '</div>';
    mainRole.insertBefore(heading, mainRole.firstChild);
});
</script>
HTML;
        $hook->add_html($html);
    }

    private static function inject_course_view_redesign(
        before_standard_top_of_body_html_generation $hook,
        int $level
    ): void {
        global $COURSE, $DB;

        $courseid = $COURSE->id;
        if ($courseid <= 1) {
            return;
        }

        $coursename = s(format_string($COURSE->fullname, true));

        // Category name.
        $catname = '';
        if (!empty($COURSE->category)) {
            $cat = $DB->get_record('course_categories', ['id' => $COURSE->category], 'name');
            if ($cat) {
                $catname = s(format_string($cat->name, true));
            }
        }

        // Participants count.
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        $participantcount = 0;
        if ($context) {
            $participantcount = count_enrolled_users($context);
        }

        // URLs for course action buttons.
        $settingsurl = (new \moodle_url('/course/edit.php', ['id' => $courseid]))->out(true);
        $enrolurl = (new \moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]))->out(true);
        $participantsurl = (new \moodle_url('/user/index.php', ['id' => $courseid]))->out(true);

        // Meta info.
        $metahtml = '';
        if ($catname) {
            $metahtml .= '<span class="lh-cv-meta-item"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>' . $catname . '</span>';
            $metahtml .= '<span class="lh-cv-meta-div">&middot;</span>';
        }
        $metahtml .= '<span class="lh-cv-meta-item"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' . $participantcount . ' Teilnehmer/innen</span>';
        $metaescaped = str_replace(["'", "\r\n", "\n", "\r"], ["\\'", '', '', ''], $metahtml);

        $html = <<<HTML
<style>
/* === LernHive Course View === */
/* Hide clutter but keep Moodle's course index drawer */
body.path-course-view .primary-navigation,
body.path-course-view nav.moremenu { display: none !important; }
body.path-course-view .breadcrumb { display: none !important; }
body.path-course-view nav[aria-label="Navigation"] { display: none !important; }
body.path-course-view .secondary-navigation { display: none !important; }
body.path-course-view #page-header { display: none !important; }
/* Content area clean-up */
body.path-course-view #page .main-inner { max-width: none !important; padding: 0 24px !important; }

/* Airy course heading */
.lh-cv-heading { margin-bottom: 20px; padding-top: 12px; }
.lh-cv-heading-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
.lh-cv-heading h1 { font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0 0 6px 0; line-height: 1.3; }
.lh-cv-meta { display: flex; align-items: center; gap: 12px; color: #5f6368; font-size: 0.85rem; }
.lh-cv-meta-item { display: flex; align-items: center; gap: 5px; }
.lh-cv-meta-item svg { color: #80868b; }
.lh-cv-meta-div { color: #dee2e6; }

/* Course action buttons */
.lh-cv-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.lh-cv-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s ease; white-space: nowrap;
    border: 1px solid #dee2e6; background: #fff; color: #5f6368;
}
.lh-cv-action-btn:hover { background: #f3f5f8; color: #194866; border-color: #c4cdd5; text-decoration: none; }
.lh-cv-action-btn.lh-cv-action-active { background: #194866; color: #fff; border-color: #194866; }
.lh-cv-action-btn.lh-cv-action-active:hover { background: #143a54; text-decoration: none; }
.lh-cv-action-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* --- Course section header collapse icon consistency --- */
body.path-course-view .course-section .course-section-header .icons-collapse-expand {
    display: inline-flex; align-items: center; justify-content: center;
}
body.path-course-view .course-section .course-section-header .section-chevron .icon,
body.path-course-view .course-section .course-section-header .icons-collapse-expand .icon,
body.path-course-view .course-section .course-section-header [data-toggle="collapse"] > .icon,
body.path-course-view .courseindex-chevron .icon,
body.path-course-view .section_action_menu + * .icon {
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    width: 28px !important; height: 28px !important; min-width: 28px !important;
    border-radius: 6px !important; background: #f3f5f8 !important;
    color: #5f6368 !important; font-size: 14px !important;
    margin: 0 !important; padding: 0 !important; transition: all 0.2s ease;
}
body.path-course-view .course-section .course-section-header [data-toggle="collapse"]:hover > .icon,
body.path-course-view .course-section .course-section-header .icons-collapse-expand:hover .icon,
body.path-course-view .section-chevron:hover .icon,
body.path-course-view .courseindex-chevron:hover .icon {
    background: rgba(25,72,102,0.08) !important; color: #194866 !important;
}

/* --- Drawer course-index toggle button --- */
body.path-course-view button[data-toggler="drawers"],
body.path-course-view [data-action="closecourseindex"],
body.path-course-view [data-action="opencourseindex"],
body.path-course-view .drawertoggle {
    border: 1px solid #e9e9e9 !important; border-radius: 6px !important;
    background: #fff !important; color: #5f6368 !important;
    padding: 4px 6px !important; transition: all 0.2s ease;
    display: inline-flex; align-items: center; justify-content: center;
}
body.path-course-view button[data-toggler="drawers"]:hover,
body.path-course-view [data-action="closecourseindex"]:hover,
body.path-course-view [data-action="opencourseindex"]:hover,
body.path-course-view .drawertoggle:hover {
    border-color: #194866 !important; color: #194866 !important; background: #f3f5f8 !important;
}
body.path-course-view button[data-toggler="drawers"] .icon,
body.path-course-view .drawertoggle .icon {
    width: 16px !important; height: 16px !important; margin: 0 !important;
}

/* --- Course index header inside drawer --- */
body.path-course-view .courseindex-header .collapseexpand,
body.path-course-view .courseindex-header button {
    border: 1px solid #e9e9e9 !important; border-radius: 6px !important;
    background: #fff !important; color: #5f6368 !important;
    padding: 3px 5px !important; transition: all 0.2s ease;
}
body.path-course-view .courseindex-header .collapseexpand:hover,
body.path-course-view .courseindex-header button:hover {
    border-color: #194866 !important; color: #194866 !important;
}

/* --- Course name / page title area refinement --- */
body.path-course-view .page-context-header .page-header-headings {
    margin: 0 !important; padding: 0 !important;
}
/* Hide double course name if page header leaks through */
body.path-course-view .page-header-headings h1:not(.lh-cv-heading h1) { display: none !important; }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Inject airy heading at the top of main content.
    var mainRole = document.querySelector("[role='main']");
    if (mainRole) {
        var heading = document.createElement("div");
        heading.className = "lh-cv-heading";
        heading.innerHTML = '<div class="lh-cv-heading-top">'
            + '<div>'
            +   '<h1>{$coursename}</h1>'
            +   '<div class="lh-cv-meta">{$metaescaped}</div>'
            + '</div>'
            + '<div class="lh-cv-actions">'
            +   '<a href="{$enrolurl}" class="lh-cv-action-btn" title="Nutzer/innen verwalten">'
            +     '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
            +     'Nutzer/innen'
            +   '</a>'
            +   '<a href="{$settingsurl}" class="lh-cv-action-btn" title="Kurseinstellungen">'
            +     '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>'
            +     'Einstellungen'
            +   '</a>'
            + '</div>'
            + '</div>';
        mainRole.insertBefore(heading, mainRole.firstChild);
    }

    // 2. Add tooltips to all collapse/expand toggles.
    document.querySelectorAll(".course-section .course-section-header [data-toggle='collapse'], .course-section .icons-collapse-expand, .section-chevron").forEach(function(el) {
        if (!el.getAttribute("title")) {
            var expanded = el.getAttribute("aria-expanded");
            el.setAttribute("title", expanded === "false" ? "Abschnitt aufklappen" : "Abschnitt zuklappen");
        }
    });

    // 3. Add tooltip to drawer course index toggle.
    document.querySelectorAll("button[data-toggler='drawers'], [data-action='closecourseindex'], [data-action='opencourseindex']").forEach(function(btn) {
        if (!btn.getAttribute("title")) {
            btn.setAttribute("title", "Kursindex ein-/ausblenden");
        }
    });

    // 4. Observe for dynamic section changes and re-apply tooltips.
    var courseContent = document.querySelector(".course-content");
    if (courseContent) {
        var obs = new MutationObserver(function() {
            document.querySelectorAll(".course-section .course-section-header [data-toggle='collapse']").forEach(function(el) {
                if (!el.getAttribute("title")) {
                    var expanded = el.getAttribute("aria-expanded");
                    el.setAttribute("title", expanded === "false" ? "Abschnitt aufklappen" : "Abschnitt zuklappen");
                }
            });
        });
        obs.observe(courseContent, { childList: true, subtree: true, attributes: true, attributeFilter: ["aria-expanded"] });
    }
});
</script>
HTML;
        $hook->add_html($html);
    }

    /**
     * Inject LernHive branding on the Moodle login page.
     * Adds the logo, subtitle, and accent styling.
     */
    private static function inject_login_branding(
        before_standard_top_of_body_html_generation $hook
    ): void {
        $html = <<<HTML
<style>
/* ── LernHive Login Override ── */
body.pagelayout-login {
    background: linear-gradient(135deg, #194866 0%, #65a1b3 100%) !important;
}
/* Hide Moodle's two-column marketing layout */
body.pagelayout-login .login-container .row,
body.pagelayout-login .login-container > .row {
    justify-content: center !important;
}
/* Hide left marketing column (welcome text, stats, image) */
body.pagelayout-login .login-container .col-xl-6:first-child,
body.pagelayout-login .login-container .login-content-area,
body.pagelayout-login .login-hero,
body.pagelayout-login .login-site-info,
body.pagelayout-login .login-identityproviders,
body.pagelayout-login .login-signup,
body.pagelayout-login .column.d-flex.align-items-center:not(:has(.login-form)),
body.pagelayout-login .login-container .row > div:not(:has(.login-form)):not(:has(.loginform)):not(:has(#login)) {
    display: none !important;
}
/* Make login column full-width and centered */
body.pagelayout-login .login-container .col-xl-6:has(.login-form),
body.pagelayout-login .login-container .col-xl-6:has(.loginform),
body.pagelayout-login .login-container .col-xl-6:has(#login),
body.pagelayout-login .login-container > .row > div:has(.login-form),
body.pagelayout-login .login-container > .row > div:has(.loginform) {
    flex: 0 0 100% !important;
    max-width: 420px !important;
    margin: 0 auto !important;
}
/* Hide Moodle default headings, subtitle */
body.pagelayout-login .login-heading,
body.pagelayout-login .login-form h2,
body.pagelayout-login .login-form-heading,
body.pagelayout-login .login-form .login-heading,
body.pagelayout-login h2.login-heading {
    display: none !important;
}
/* Hide "Anmelden bei 'Site'" text */
body.pagelayout-login .login-form-username ~ p,
body.pagelayout-login .login-form p.login-heading-desc,
body.pagelayout-login .login-form-subtitle {
    display: none !important;
}
/* Hide page-level headings shown above form */
body.pagelayout-login #page-content h2,
body.pagelayout-login .login-wrapper > h2,
body.pagelayout-login .main-inner > h2 {
    display: none !important;
}
/* Ensure #page is centered flex */
body.pagelayout-login #page {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 100vh !important;
    background: transparent !important;
}
body.pagelayout-login #page-wrapper,
body.pagelayout-login #page-content {
    background: transparent !important;
}
/* Hide navbar, header, breadcrumbs */
body.pagelayout-login .navbar,
body.pagelayout-login #page-header,
body.pagelayout-login .primary-navigation,
body.pagelayout-login nav.moremenu,
body.pagelayout-login .breadcrumb {
    display: none !important;
}
/* Card styling */
body.pagelayout-login .login-wrapper,
body.pagelayout-login .login-form-wrapper,
body.pagelayout-login .loginform,
body.pagelayout-login #login,
body.pagelayout-login .login-form {
    background: #fff !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 40px rgba(25, 72, 102, 0.15) !important;
    padding: 40px 32px !important;
    border: none !important;
    position: relative;
    overflow: hidden;
    max-width: 420px !important;
    margin: 0 auto !important;
}
/* Orange accent line */
body.pagelayout-login .login-wrapper::before,
body.pagelayout-login .login-form::before,
body.pagelayout-login .loginform::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f98012 0%, #f98012 50%, transparent 100%);
}
/* Pill inputs */
body.pagelayout-login .form-control,
body.pagelayout-login input[type="text"],
body.pagelayout-login input[type="password"] {
    border-radius: 24px !important;
    padding: 12px 20px !important;
    border: 1px solid #dee2e6 !important;
}
body.pagelayout-login .form-control:focus,
body.pagelayout-login input[type="text"]:focus,
body.pagelayout-login input[type="password"]:focus {
    border-color: #194866 !important;
    box-shadow: 0 0 0 3px rgba(25, 72, 102, 0.12) !important;
}
/* Labels */
body.pagelayout-login .form-label,
body.pagelayout-login label {
    font-weight: 500;
    font-size: 0.875rem;
    color: #194866;
}
/* Login button — pill */
body.pagelayout-login .btn-primary,
body.pagelayout-login #loginbtn,
body.pagelayout-login button[type="submit"] {
    width: 100%;
    padding: 12px 24px !important;
    font-weight: 600;
    border-radius: 24px !important;
    margin-top: 16px;
    background: #194866 !important;
    border-color: #194866 !important;
}
body.pagelayout-login .btn-primary:hover,
body.pagelayout-login #loginbtn:hover {
    background: #0f2e3f !important;
    border-color: #0f2e3f !important;
}
/* Forgot password */
body.pagelayout-login .login-form-forgotpassword,
body.pagelayout-login .forgetpass {
    text-align: center;
    margin-top: 16px;
}
body.pagelayout-login .login-form-forgotpassword a,
body.pagelayout-login .forgetpass a {
    font-size: 0.875rem;
    color: #5f6368;
    text-decoration: none;
}
/* Language selector */
body.pagelayout-login .langmenu,
body.pagelayout-login .login-languagemenu {
    text-align: center;
    margin-top: 16px;
}
/* Footer */
body.pagelayout-login #page-footer {
    background: transparent !important;
    border: none !important;
    color: rgba(255,255,255,0.6);
    font-size: 0.875rem;
    text-align: center;
}
body.pagelayout-login #page-footer a {
    color: rgba(255,255,255,0.8);
}
/* Hide cookie/alert banners */
body.pagelayout-login .alert-info { display: none; }
/* Hide any remaining site info on login */
body.pagelayout-login .login-site-info,
body.pagelayout-login .login-identityproviders-label,
body.pagelayout-login .login-divider { display: none !important; }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Hide the marketing / left column aggressively.
    var loginContainer = document.querySelector(".login-container");
    if (loginContainer) {
        var row = loginContainer.querySelector(".row");
        if (row) {
            var cols = row.children;
            for (var i = 0; i < cols.length; i++) {
                var hasForm = cols[i].querySelector(".login-form, .loginform, #login, form#login");
                if (!hasForm) {
                    cols[i].style.display = "none";
                }
            }
        }
    }

    // 2. Add LernHive logo + subtitle above login form.
    var loginForm = document.querySelector(".login-form, .loginform, #login, .login-wrapper");
    if (loginForm) {
        if (!loginForm.querySelector(".lh-login-logo")) {
            var logoDiv = document.createElement("div");
            logoDiv.className = "lh-login-logo";
            logoDiv.innerHTML = '<div style="text-align:center;margin-bottom:8px;">'
                + '<span style="font-size:1.8rem;font-weight:700;letter-spacing:-0.5px;">'
                + '<span style="color:#194866;">Lern</span><span style="color:#f98012;">Hive</span></span></div>'
                + '<div style="text-align:center;font-size:0.95rem;color:#5f6368;margin-bottom:24px;">Willkommen zurück</div>';
            loginForm.insertBefore(logoDiv, loginForm.firstChild);
        }

        // 3. Hide ALL default headings inside the form area.
        loginForm.querySelectorAll("h2, .login-heading, h3, h4").forEach(function(h) {
            if (!h.closest(".lh-login-logo")) {
                h.style.display = "none";
            }
        });

        // 4. Hide "Anmelden bei 'Site'" description text.
        loginForm.querySelectorAll("p").forEach(function(p) {
            var text = p.textContent.toLowerCase();
            if (text.indexOf("anmelden bei") !== -1 || text.indexOf("log in") !== -1
                || text.indexOf("sign in") !== -1) {
                p.style.display = "none";
            }
        });
    }

    // 5. Also target any headings above the login form wrapper.
    document.querySelectorAll("#page-content h2, .main-inner > h2, .login-wrapper > h2").forEach(function(h) {
        h.style.display = "none";
    });

    // 6. Hide navbar completely on login.
    var navbar = document.querySelector(".navbar");
    if (navbar) {
        navbar.style.display = "none";
    }
});
</script>
HTML;
        $hook->add_html($html);
    }
}
