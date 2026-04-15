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
 * Renderable launcher output model.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_launcher\output;

use local_lernhive_launcher\action;
use renderer_base;
use renderable;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable launcher view model.
 */
class launcher implements renderable, templatable {
    /** @var action[] */
    protected array $actions;

    /**
     * Constructor.
     *
     * @param action[] $actions
     */
    public function __construct(array $actions) {
        $this->actions = $actions;
    }

    /**
     * Export template context.
     *
     * @param renderer_base $output
     * @return array<string, mixed>
     */
    public function export_for_template(renderer_base $output): array {
        $actions = [];

        foreach ($this->actions as $action) {
            $actions[] = [
                'id' => $action->id,
                'label' => $action->label,
                'description' => $action->description,
                'url' => $action->url->out(false),
                'iscreatecourse' => $action->icon === 'book-open',
                'iscontenthub' => $action->icon === 'layout-grid',
                'isreports' => $action->icon === 'chart-bar',
                'iscreatesnack' => $action->icon === 'circle-play',
                'iscreatecommunity' => $action->icon === 'users',
            ];
        }

        return [
            'title' => get_string('launchertitle', 'local_lernhive_launcher'),
            'description' => get_string('launcherintro', 'local_lernhive_launcher'),
            'empty' => empty($actions),
            'emptytext' => get_string('noactionsavailable', 'local_lernhive_launcher'),
            'actions' => $actions,
        ];
    }
}
