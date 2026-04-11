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
 * Unit tests for card_registry — the ContentHub orchestrator.
 *
 * Uses an injected plugin detector so tests are independent of which
 * sibling plugins are actually installed in the test Moodle instance.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_contenthub\card_registry
 */
final class card_registry_test extends advanced_testcase {

    /**
     * Build a fake detector from an allow-list of installed components.
     *
     * @param string[] $installed
     * @return callable (string): bool
     */
    private function fake_detector(array $installed): callable {
        return static function (string $component) use ($installed): bool {
            return in_array($component, $installed, true);
        };
    }

    /**
     * Index the card list by key for easy lookup.
     *
     * @param card[] $cards
     * @return array<string,card>
     */
    private function index_by_key(array $cards): array {
        $bykey = [];
        foreach ($cards as $card) {
            $bykey[$card->key] = $card;
        }
        return $bykey;
    }

    /**
     * Happy path: both sibling plugins installed, AI card hidden.
     *
     * Expect three cards (copy, template, library) all AVAILABLE.
     * AI card must NOT appear — it is gated behind the admin setting
     * and tests pass `showaicard=false` explicitly.
     */
    public function test_all_siblings_installed(): void {
        $this->resetAfterTest();

        $detector = $this->fake_detector([
            'local_lernhive_copy',
            'local_lernhive_library',
        ]);

        $cards = card_registry::get_cards($detector, false);
        $this->assertCount(3, $cards);

        $bykey = $this->index_by_key($cards);
        $this->assertArrayHasKey('copy', $bykey);
        $this->assertArrayHasKey('template', $bykey);
        $this->assertArrayHasKey('library', $bykey);
        $this->assertArrayNotHasKey('ai', $bykey);

        $this->assertSame(card::STATUS_AVAILABLE, $bykey['copy']->status);
        $this->assertSame(card::STATUS_AVAILABLE, $bykey['template']->status);
        $this->assertSame(card::STATUS_AVAILABLE, $bykey['library']->status);

        // Template must be a sub-action of the copy plugin.
        $templateurl = $bykey['template']->url;
        $this->assertNotNull($templateurl);
        $this->assertStringContainsString('/local/lernhive_copy/index.php', $templateurl->out(false));
        $this->assertSame('template', $templateurl->get_param('source'));
    }

    /**
     * No sibling plugins at all: every card renders as UNAVAILABLE,
     * but the hub page still builds without errors.
     */
    public function test_no_siblings_installed(): void {
        $this->resetAfterTest();

        $detector = $this->fake_detector([]);
        $cards = card_registry::get_cards($detector, false);

        $this->assertCount(3, $cards);
        foreach ($cards as $card) {
            $this->assertSame(
                card::STATUS_UNAVAILABLE,
                $card->status,
                "Card '{$card->key}' should be unavailable when no siblings installed"
            );
            $this->assertNull($card->url, "Unavailable card '{$card->key}' must expose no URL");
        }
    }

    /**
     * Mixed state: only the library plugin is installed. Copy and
     * Template must both be UNAVAILABLE because Template piggybacks
     * on the copy plugin.
     */
    public function test_only_library_installed(): void {
        $this->resetAfterTest();

        $detector = $this->fake_detector(['local_lernhive_library']);
        $cards = card_registry::get_cards($detector, false);
        $bykey = $this->index_by_key($cards);

        $this->assertSame(card::STATUS_UNAVAILABLE, $bykey['copy']->status);
        $this->assertSame(card::STATUS_UNAVAILABLE, $bykey['template']->status);
        $this->assertSame(card::STATUS_AVAILABLE, $bykey['library']->status);
    }

    /**
     * The AI card only appears when the admin setting is enabled. It
     * is always COMING_SOON — there is no production target for R1.
     */
    public function test_ai_card_is_optional_and_always_coming_soon(): void {
        $this->resetAfterTest();

        $detector = $this->fake_detector([]);

        $without = card_registry::get_cards($detector, false);
        $this->assertCount(3, $without);

        $with = card_registry::get_cards($detector, true);
        $this->assertCount(4, $with);

        $ai = $this->index_by_key($with)['ai'];
        $this->assertSame(card::STATUS_COMING_SOON, $ai->status);
        $this->assertNull($ai->url);
    }

    /**
     * Ordering is part of the contract (Copy → Template → Library → AI).
     * Product and mockup rely on this ordering.
     */
    public function test_card_ordering_is_stable(): void {
        $this->resetAfterTest();

        $detector = $this->fake_detector([
            'local_lernhive_copy',
            'local_lernhive_library',
        ]);
        $cards = card_registry::get_cards($detector, true);

        $keys = array_map(fn(card $c): string => $c->key, $cards);
        $this->assertSame(['copy', 'template', 'library', 'ai'], $keys);
    }
}
