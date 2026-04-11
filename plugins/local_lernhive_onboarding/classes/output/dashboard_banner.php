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
 * Trainer learning-path dashboard banner — renderable.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding\output;

use local_lernhive_onboarding\tour_manager;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for the dashboard banner pushed to trainers on `/my/`.
 *
 * The banner is a lightweight, hook-injected element that summarises the
 * user's current Level 1 progress and routes them to the learning-path
 * overview. It is only built for users who pass `banner_gate::should_show`.
 */
class dashboard_banner implements renderable, templatable {

    /** @var int */
    private $userid;

    /** @var int */
    private $level;

    /**
     * @param int $userid The user the banner is being rendered for.
     * @param int $level  The LernHive level to show progress for (usually 1).
     */
    public function __construct(int $userid, int $level = 1) {
        $this->userid = $userid;
        $this->level = $level;
    }

    /**
     * Build the template context for `templates/dashboard_banner.mustache`.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $progress = tour_manager::get_level_progress($this->level, $this->userid);

        $data = new stdClass();
        $data->toursurl = (new \moodle_url('/local/lernhive_onboarding/tours.php'))->out(false);
        $data->percent = (int) $progress['percent'];
        $data->completed = (int) $progress['completed_tours'];
        $data->total = (int) $progress['total_tours'];
        $data->remaining = max(0, $data->total - $data->completed);
        $data->hasprogress = $data->completed > 0;
        $data->nothingyet = $data->completed === 0;

        $data->heading = get_string('banner_heading', 'local_lernhive_onboarding');
        $data->intro = get_string(
            $data->nothingyet ? 'banner_intro_start' : 'banner_intro_resume',
            'local_lernhive_onboarding'
        );
        $data->cta = get_string(
            $data->nothingyet ? 'banner_cta_start' : 'banner_cta_resume',
            'local_lernhive_onboarding'
        );
        $data->progresslabel = get_string(
            'banner_progress_label',
            'local_lernhive_onboarding',
            (object) ['done' => $data->completed, 'total' => $data->total]
        );

        return $data;
    }
}
