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

namespace local_lernhive\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a feature override row changes.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feature_override_changed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_lernhive_feature_overrides';
    }

    public static function get_name() {
        return get_string('event_feature_override_changed', 'local_lernhive');
    }

    public function get_description() {
        $featureid = (string) ($this->other['feature_id'] ?? '');
        $action = (string) ($this->other['action'] ?? 'set');
        return "Feature override '{$featureid}' was {$action} in local_lernhive.";
    }

    public function get_url() {
        return new \moodle_url('/admin/settings.php', ['section' => 'local_lernhive_level_configuration']);
    }

    public static function get_other_mapping() {
        return false;
    }
}
