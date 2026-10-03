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

namespace tiny_fontsizecolor\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the font size and font colour list parser.
 *
 * @package     tiny_fontsizecolor
 * @category    test
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(fontlist::class)]
final class fontlist_test extends \advanced_testcase {
    /**
     * Font sizes are whole numbers within range, sorted and de-duplicated.
     */
    public function test_parse_fontsizes(): void {
        $invalid = [];
        $sizes = fontlist::parse_fontsizes("12\r\n 9 \n\n8\n12\n5\n73\n72\n6\n10.5\n-8\n1e1\n<b>9</b>", $invalid);

        $this->assertSame([6, 8, 9, 12, 72], $sizes);
        $this->assertSame(['5', '73', '10.5', '-8', '1e1', '<b>9</b>'], $invalid);
    }

    /**
     * Colours must be strict hex values; labels are cleaned, and duplicates are reported with the first kept.
     */
    public function test_parse_fontcolors(): void {
        $invalid = [];
        $duplicates = [];
        $colors = fontlist::parse_fontcolors(implode("\n", [
            '#FF0000|Red',
            '#0f0',
            '#0000ff|<script>alert(1)</script>Blue',
            'red|Red',
            '#ff0000;background:url(x)|Bad',
            'expression(alert(1))',
            '#12345|Bad',
            '#abc|A|B',
            '#ff0000|Scarlet',
            '#00FF00|Lime',
        ]), $invalid, $duplicates);

        $this->assertSame([
            ['value' => '#ff0000', 'label' => 'Red'],
            ['value' => '#00ff00', 'label' => '#00FF00'],
            ['value' => '#0000ff', 'label' => 'alert(1)Blue'],
            ['value' => '#aabbcc', 'label' => 'AB'],
        ], $colors);
        $this->assertSame(['red|Red', '#ff0000;background:url(x)|Bad', 'expression(alert(1))', '#12345|Bad'], $invalid);
        $this->assertSame(['#ff0000|Scarlet', '#00FF00|Lime'], $duplicates);
    }

    /**
     * Long colour labels are truncated.
     */
    public function test_parse_fontcolors_truncates_label(): void {
        $colors = fontlist::parse_fontcolors('#000000|' . str_repeat('x', 80));
        $this->assertSame(fontlist::MAX_LABEL_LENGTH, \core_text::strlen($colors[0]['label']));
    }

    /**
     * The defaults are used until something valid is configured.
     */
    public function test_get_lists_defaults(): void {
        $this->resetAfterTest();

        unset_config('fontsizes', 'tiny_fontsizecolor');
        unset_config('fontcolors', 'tiny_fontsizecolor');
        $this->assertSame([8, 9, 10, 11, 12], fontlist::get_fontsizes());
        $this->assertCount(7, fontlist::get_fontcolors());

        set_config('fontsizes', "99\nabc", 'tiny_fontsizecolor');
        set_config('fontcolors', 'nope', 'tiny_fontsizecolor');
        $this->assertSame([8, 9, 10, 11, 12], fontlist::get_fontsizes());
        $this->assertSame(['value' => '#000000', 'label' => 'Black'], fontlist::get_fontcolors()[0]);

        set_config('fontsizes', "14\n7\n40", 'tiny_fontsizecolor');
        set_config('fontcolors', "#123456|Mine", 'tiny_fontsizecolor');
        $this->assertSame([7, 14, 40], fontlist::get_fontsizes());
        $this->assertSame([['value' => '#123456', 'label' => 'Mine']], fontlist::get_fontcolors());
    }

    /**
     * The default settings round-trip through the parser unchanged.
     */
    public function test_defaults_round_trip(): void {
        $this->assertSame(fontlist::DEFAULT_FONTSIZES, fontlist::parse_fontsizes(fontlist::default_fontsizes_setting()));
        $colors = fontlist::parse_fontcolors(fontlist::default_fontcolors_setting());
        $this->assertSame(fontlist::default_fontcolors_setting(), fontlist::format_fontcolors($colors));
    }
}
