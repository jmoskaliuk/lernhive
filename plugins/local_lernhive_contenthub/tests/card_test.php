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
 * Unit tests for the ContentHub card value object.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub;

use advanced_testcase;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_contenthub\card
 */
final class card_test extends advanced_testcase {

    /**
     * Available card with a URL exports `hasurl=true` and the URL string.
     */
    public function test_available_card_exports_hasurl_and_url(): void {
        $this->resetAfterTest();

        $url = new moodle_url('/local/lernhive_copy/index.php');
        $card = new card(
            key: 'copy',
            titlestring: 'card_copy_title',
            descstring: 'card_copy_desc',
            ctastring: 'card_copy_cta',
            icon: 'X',
            url: $url,
            status: card::STATUS_AVAILABLE,
        );

        $ctx = $card->to_template_context();

        $this->assertSame('copy', $ctx['key']);
        $this->assertSame('X', $ctx['icon']);
        $this->assertTrue($ctx['hasurl']);
        $this->assertTrue($ctx['available']);
        $this->assertFalse($ctx['coming_soon']);
        $this->assertFalse($ctx['unavailable']);
        $this->assertSame($url->out(false), $ctx['url']);
        $this->assertNotEmpty($ctx['title']);
        $this->assertNotEmpty($ctx['description']);
        $this->assertNotEmpty($ctx['cta']);
        $this->assertNotEmpty($ctx['statuslabel']);
    }

    /**
     * Coming-soon card has no URL and `hasurl=false` even if a URL is
     * technically set — the status is what controls rendering.
     */
    public function test_coming_soon_card_is_not_interactive(): void {
        $this->resetAfterTest();

        $card = new card(
            key: 'ai',
            titlestring: 'card_ai_title',
            descstring: 'card_ai_desc',
            ctastring: 'card_ai_cta',
            icon: '*',
            url: null,
            status: card::STATUS_COMING_SOON,
        );

        $ctx = $card->to_template_context();

        $this->assertFalse($ctx['hasurl']);
        $this->assertFalse($ctx['available']);
        $this->assertTrue($ctx['coming_soon']);
        $this->assertFalse($ctx['unavailable']);
        $this->assertSame('', $ctx['url']);
    }

    /**
     * Unavailable card (missing sibling plugin) renders as disabled.
     */
    public function test_unavailable_card_is_not_interactive(): void {
        $this->resetAfterTest();

        $card = new card(
            key: 'library',
            titlestring: 'card_library_title',
            descstring: 'card_library_desc',
            ctastring: 'card_library_cta',
            icon: 'B',
            url: null,
            status: card::STATUS_UNAVAILABLE,
        );

        $ctx = $card->to_template_context();

        $this->assertFalse($ctx['hasurl']);
        $this->assertFalse($ctx['available']);
        $this->assertFalse($ctx['coming_soon']);
        $this->assertTrue($ctx['unavailable']);
    }

    /**
     * A URL passed alongside a non-available status must not be exposed
     * as an interactive link — this is the regression guard.
     */
    public function test_url_is_ignored_when_status_is_not_available(): void {
        $this->resetAfterTest();

        $card = new card(
            key: 'copy',
            titlestring: 'card_copy_title',
            descstring: 'card_copy_desc',
            ctastring: 'card_copy_cta',
            icon: 'X',
            url: new moodle_url('/local/lernhive_copy/index.php'),
            status: card::STATUS_UNAVAILABLE,
        );

        $ctx = $card->to_template_context();

        $this->assertFalse($ctx['hasurl'], 'Unavailable card must never expose hasurl=true');
    }
}
