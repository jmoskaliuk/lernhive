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
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'lernhive';
$THEME->parents = ['boost'];

$THEME->prescsscallback = 'theme_lernhive_get_pre_scss';
$THEME->extrascsscallback = 'theme_lernhive_get_extra_scss';

$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->editor_scss = ['editor'];
$THEME->usescss = true;
$THEME->hidefromselector = false;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->enable_dock = false;
$THEME->undeletableblocktypes = [];
$THEME->requiredblocks = '';
$THEME->usefallback = true;

// LernHive block regions (since 0.9.3):
//  - content-top        : full-width strip above the main content area
//  - content-bottom     : full-width strip below the main content area (default region for new blocks)
//  - sidebar-bottom     : inside the left sidebar, below the primary navigation
//  - footer-left        : three-column footer, left slot
//  - footer-center      : three-column footer, center slot
//  - footer-right       : three-column footer, right slot
// The legacy right-hand block drawer ('side-pre') has been removed. Blocks still
// exist as a concept — they now live in clearly scoped, predictable regions.
$lhregions = [
    'content-top',
    'content-bottom',
    'sidebar-bottom',
    'footer-left',
    'footer-center',
    'footer-right',
];
$lhdefaultregion = 'content-bottom';

$THEME->layouts = [
    'base' => [
        'file' => 'drawers.php',
        'regions' => [],
    ],
    'standard' => [
        'file' => 'drawers.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
    ],
    'course' => [
        'file' => 'course.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
        'options' => ['langmenu' => true],
    ],
    'incourse' => [
        'file' => 'course.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
    ],
    'frontpage' => [
        'file' => 'drawers.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
        'options' => ['nonavbar' => true],
    ],
    'admin' => [
        'file' => 'admin.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
    ],
    'mydashboard' => [
        'file' => 'admin.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
        'options' => ['langmenu' => true],
    ],
    'login' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'popup' => [
        'file' => 'columns1.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
            'activityheader' => [
                'notitle' => true,
                'nocompletion' => true,
                'nodescription' => true,
            ],
        ],
    ],
    'maintenance' => [
        'file' => 'admin.php',
        'regions' => [],
    ],
    'report' => [
        'file' => 'drawers.php',
        'regions' => $lhregions,
        'defaultregion' => $lhdefaultregion,
    ],
];

unset($lhregions, $lhdefaultregion);
