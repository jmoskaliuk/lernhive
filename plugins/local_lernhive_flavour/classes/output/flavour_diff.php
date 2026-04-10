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
 * Renderable for the confirm-diff dialog shown before applying a flavour
 * that would overwrite already-customised settings.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\output;

use local_lernhive_flavour\flavour_manager;
use local_lernhive_flavour\flavour_registry;
use renderable;
use templatable;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Diff confirm view.
 */
class flavour_diff implements renderable, templatable {

    /** @var string Target flavour key. */
    private string $targetkey;

    /**
     * @param string $targetkey
     */
    public function __construct(string $targetkey) {
        $this->targetkey = $targetkey;
    }

    /**
     * Build the template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $target = flavour_registry::get($this->targetkey);
        if ($target === null) {
            throw new \invalid_parameter_exception("Unknown flavour key: {$this->targetkey}");
        }

        $rows = [];
        foreach (flavour_manager::diff($this->targetkey) as $entry) {
            $rows[] = [
                'component'  => $entry['component'],
                'name'       => $entry['name'],
                'current'    => self::format_value($entry['current']),
                'target'     => self::format_value($entry['target']),
                'changes'    => (bool) $entry['changes'],
            ];
        }

        return [
            'heading'     => get_string('diff_heading', 'local_lernhive_flavour'),
            'intro'       => get_string('diff_intro', 'local_lernhive_flavour', $target->get_label()),
            'rows'        => $rows,
            'hasrows'     => !empty($rows),
            'targetkey'   => $this->targetkey,
            'targetlabel' => $target->get_label(),
            'sesskey'     => sesskey(),
            'cancelurl'   => (new \moodle_url('/local/lernhive_flavour/admin_flavour.php'))->out(false),
        ];
    }

    /**
     * Format a value for display. null becomes a "(not set)" placeholder.
     *
     * @param mixed $value
     * @return string
     */
    private static function format_value($value): string {
        if ($value === null) {
            return get_string('value_unset', 'local_lernhive_flavour');
        }
        return (string) $value;
    }
}
