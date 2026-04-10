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
 * Theme configuration for LernHive.
 *
 * Direct Boost child theme. Inherits all SCSS and layouts from Boost,
 * adds custom variables (pre) and component styles (extra).
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'lernhive';
$THEME->parents = ['boost'];

// Inject LernHive variables before Boost's SCSS compilation.
$THEME->prescsscallback = 'theme_lernhive_get_pre_scss';

// Append LernHive component styles after Boost's SCSS compilation.
$THEME->extrascsscallback = 'theme_lernhive_get_extra_scss';

// Let Boost handle the main SCSS — do NOT set $THEME->scss.
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usescss = true;
$THEME->hidefromselector = false;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->enable_dock = false;
$THEME->undeletableblocktypes = [];
$THEME->requiredblocks = '';
