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
 * Plugin-specific Behat step definitions — reserved placeholder.
 *
 * ContentHub's R1 Behat feature only uses core navigation steps, so
 * no plugin-specific definitions are needed yet. The class exists as
 * an anchor point for future helpers (e.g. once the launcher plugin
 * lets a non-admin reach the hub directly and we need a page resolver).
 *
 * @package    local_lernhive_contenthub
 * @category   test
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Reserved placeholder — intentionally empty, extends behat_base so
 * Moodle's Behat loader is happy without contributing new steps.
 */
class behat_local_lernhive_contenthub extends behat_base {
}
