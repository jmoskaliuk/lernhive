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

namespace local_lernhive\admin;

defined('MOODLE_INTERNAL') || die();

use local_lernhive\feature\definition;
use local_lernhive\feature\override_store;

/**
 * Admin select setting backed by local_lernhive feature overrides.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feature_override_setting extends \admin_setting_configselect {

    /** @var string Canonical local_lernhive feature id. */
    private string $featureid;

    /**
     * Build the override selector.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $featureid
     */
    public function __construct(string $name, string $visiblename, string $description, string $featureid) {
        $this->featureid = $featureid;
        parent::__construct($name, $visiblename, $description, 'default', self::choices());
        $this->nosave = true;
    }

    /**
     * Read the currently effective dropdown value.
     *
     * - no row => "default"
     * - row with NULL level => "disabled"
     * - row with numeric level => "1".."5"
     *
     * @return string
     */
    public function get_setting() {
        $row = override_store::get_effective_override($this->featureid);
        if ($row === null) {
            return 'default';
        }
        if ($row->override_level === null || $row->override_level === '') {
            return 'disabled';
        }
        return (string) (int) $row->override_level;
    }

    /**
     * Persist one setting value through override_store.
     *
     * @param mixed $data
     * @return string Empty string on success, error string otherwise.
     */
    public function write_setting($data) {
        $value = trim((string) $data);
        $choices = self::choices();
        if (!array_key_exists($value, $choices)) {
            return get_string('errorsetting', 'admin');
        }

        $row = override_store::get_effective_override($this->featureid);
        $currentsource = $row ? (string) ($row->source ?? '') : '';
        $currentlevel = $row ? (($row->override_level === null || $row->override_level === '') ? null : (int) $row->override_level) : null;

        if ($value === 'default') {
            override_store::clear_admin_override($this->featureid);
            return '';
        }

        $targetlevel = ($value === 'disabled') ? null : (int) $value;

        // Avoid silently converting unchanged flavor presets into admin rows
        // on "Save changes" when the dropdown value was not touched.
        if ($currentsource === override_store::SOURCE_FLAVOR_PRESET && $currentlevel === $targetlevel) {
            return '';
        }

        global $USER;
        $userid = isset($USER->id) ? (int) $USER->id : null;
        override_store::set_admin_override($this->featureid, $targetlevel, $userid);
        return '';
    }

    /**
     * Available dropdown options.
     *
     * @return array<string, string>
     */
    private static function choices(): array {
        return [
            'default' => get_string('setting_feature_override_default', 'local_lernhive'),
            'disabled' => get_string('setting_feature_override_disabled', 'local_lernhive'),
            (string) definition::MIN_LEVEL => get_string('level_explorer', 'local_lernhive'),
            '2' => get_string('level_creator', 'local_lernhive'),
            '3' => get_string('level_pro', 'local_lernhive'),
            '4' => get_string('level_expert', 'local_lernhive'),
            (string) definition::MAX_LEVEL => get_string('level_master', 'local_lernhive'),
        ];
    }
}
