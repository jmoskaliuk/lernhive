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
 * Immutable launcher action definition.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_launcher;

defined('MOODLE_INTERNAL') || die();

/**
 * Value object for launcher actions.
 */
class action {
    /** @var string */
    public string $id;
    /** @var string */
    public string $label;
    /** @var string */
    public string $description;
    /** @var string */
    public string $icon;
    /** @var \moodle_url */
    public \moodle_url $url;
    /** @var int */
    public int $sortorder;

    /**
     * Constructor.
     *
     * @param string $id
     * @param string $label
     * @param string $description
     * @param string $icon
     * @param \moodle_url $url
     * @param int $sortorder
     */
    public function __construct(
        string $id,
        string $label,
        string $description,
        string $icon,
        \moodle_url $url,
        int $sortorder
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->description = $description;
        $this->icon = $icon;
        $this->url = $url;
        $this->sortorder = $sortorder;
    }
}
