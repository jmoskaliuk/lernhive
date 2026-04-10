/**
 * LernHive Course Settings Filter
 *
 * Simplifies the course settings form based on the teacher's LernHive level.
 * For Level 1 (Entdecker), most fields and sections are hidden to reduce
 * complexity. Higher levels progressively reveal more settings.
 *
 * @module     local_lernhive/course_settings_filter
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    /**
     * Slugify a string for use as course shortname.
     * @param {string} str The input string.
     * @return {string} The slugified string.
     */
    function slugify(str) {
        return str
            .toLowerCase()
            .replace(/[äÄ]/g, 'ae')
            .replace(/[öÖ]/g, 'oe')
            .replace(/[üÜ]/g, 'ue')
            .replace(/[ß]/g, 'ss')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '')
            .substring(0, 100);
    }

    /**
     * Hide an element by selector.
     * @param {string} selector CSS selector.
     */
    function hide(selector) {
        var el = document.querySelector(selector);
        if (el) {
            el.style.display = 'none';
        }
    }

    /**
     * Set a form field value.
     * @param {string} id Element ID.
     * @param {*} value The value to set.
     */
    function setVal(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value;
        }
    }

    /**
     * Apply Level 1 (Entdecker) simplifications.
     */
    function applyLevel1() {
        // --- General section: hide shortname, category, visibility, idnumber ---
        hide('#fitem_id_shortname');
        hide('#fitem_id_category');
        hide('#fitem_id_visible');
        hide('#fitem_id_idnumber');

        // Ensure visibility is set to "show" (value 1).
        setVal('id_visible', '1');

        // Hide enddate row (not needed for Entdecker).
        hide('#fitem_id_enddate');

        // Hide time selects in startdate (keep only day/month/year).
        var hourEl = document.getElementById('id_startdate_hour');
        if (hourEl) {
            hourEl.style.display = 'none';
        }
        var minuteEl = document.getElementById('id_startdate_minute');
        if (minuteEl) {
            minuteEl.style.display = 'none';
        }

        // --- Auto-generate shortname from fullname ---
        var fullnameEl = document.getElementById('id_fullname');
        var shortnameEl = document.getElementById('id_shortname');
        if (fullnameEl && shortnameEl) {
            // Generate on input change.
            fullnameEl.addEventListener('input', function() {
                shortnameEl.value = slugify(fullnameEl.value);
            });
            // Also generate on form submit if shortname is empty.
            var form = fullnameEl.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (!shortnameEl.value.trim()) {
                        shortnameEl.value = slugify(fullnameEl.value);
                    }
                });
            }
        }

        // --- Hide entire sections ---
        hide('#id_courseformathdr');   // Course format
        hide('#id_appearancehdr');    // Appearance
        hide('#id_filehdr');          // Files and uploads
        hide('#id_groups');           // Groups
        hide('#id_tagshdr');          // Tags

        // --- Completion tracking: set defaults and simplify ---
        // Enable completion = Yes.
        setVal('id_enablecompletion', '1');
        // Show activity completion = Yes.
        setVal('id_showcompletionconditions', '1');
        // Hide the completion fields (keep section header visible but lock values).
        hide('#fitem_id_enablecompletion');
        hide('#fitem_id_showcompletionconditions');
    }

    return {
        /**
         * Initialize the course settings filter.
         * @param {number} level The user's LernHive level.
         */
        init: function(level) {
            // Only modify the course edit form.
            if (!document.getElementById('id_fullname')) {
                return;
            }

            if (level <= 1) {
                applyLevel1();
            }
            // Future: add applyLevel2(), applyLevel3() etc.
        }
    };
});
