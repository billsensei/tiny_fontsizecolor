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

namespace tiny_fontsizecolor\admin;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the font size and font colour admin settings.
 *
 * @package     tiny_fontsizecolor
 * @category    test
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(setting_list::class)]
#[CoversClass(setting_fontsizes::class)]
#[CoversClass(setting_fontcolors::class)]
final class setting_list_test extends \advanced_testcase {
    /**
     * Valid sizes are stored sorted and de-duplicated; an empty list or any invalid line rejects the save.
     */
    public function test_fontsizes_write_setting(): void {
        $this->resetAfterTest();
        $setting = new setting_fontsizes('tiny_fontsizecolor/fontsizes', 'Sizes', '', '8');

        $this->assertSame('', $setting->write_setting("14\r\n9\n14"));
        $this->assertSame("9\n14", get_config('tiny_fontsizecolor', 'fontsizes'));

        $this->assertSame(get_string('error_empty', 'tiny_fontsizecolor'), $setting->write_setting(" \n\n"));
        $this->assertSame("9\n14", get_config('tiny_fontsizecolor', 'fontsizes'));

        $error = $setting->write_setting("10\n200\nbig");
        $this->assertStringContainsString('200, big', $error);
        $this->assertSame("9\n14", get_config('tiny_fontsizecolor', 'fontsizes'));
    }

    /**
     * Valid colours are stored normalised; any invalid or duplicate line rejects the save.
     */
    public function test_fontcolors_write_setting(): void {
        $this->resetAfterTest();
        $setting = new setting_fontcolors('tiny_fontsizecolor/fontcolors', 'Colours', '', '#000000');

        $this->assertSame('', $setting->write_setting("#ABC|Grey\n#ff0000"));
        $this->assertSame("#aabbcc|Grey\n#ff0000|#FF0000", get_config('tiny_fontsizecolor', 'fontcolors'));

        $error = $setting->write_setting("#FFF|White\n#ffffff|Also white");
        $this->assertSame(get_string('error_duplicates', 'tiny_fontsizecolor', '#ffffff|Also white'), $error);
        $this->assertSame("#aabbcc|Grey\n#ff0000|#FF0000", get_config('tiny_fontsizecolor', 'fontcolors'));

        $error = $setting->write_setting("#fff\nurl(evil)|x");
        $this->assertStringContainsString('url(evil)|x', $error);
        $this->assertSame("#aabbcc|Grey\n#ff0000|#FF0000", get_config('tiny_fontsizecolor', 'fontcolors'));
    }
}
