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

namespace tiny_fontsizecolor;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Tests for the upgrade steps.
 *
 * @package     tiny_fontsizecolor
 * @category    test
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class upgrade_test extends \advanced_testcase {
    /**
     * Run the upgrade as if the site was on the given version.
     *
     * @param int $from The version the site is upgrading from.
     */
    private function run_upgrade(int $from): void {
        global $CFG;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once(__DIR__ . '/../db/upgrade.php');

        set_config('version', $from, 'tiny_fontsizecolor');
        $this->assertTrue(xmldb_tiny_fontsizecolor_upgrade($from));
    }

    /**
     * A site still on the old 8-18 default gets the new 8-12 default.
     */
    public function test_old_default_sizes_are_replaced(): void {
        $this->resetAfterTest();
        set_config('fontsizes', implode("\n", range(8, 18)), 'tiny_fontsizecolor');

        $this->run_upgrade(2026092600);

        $this->assertSame("8\n9\n10\n11\n12", get_config('tiny_fontsizecolor', 'fontsizes'));
        $this->assertEquals(2026092700, get_config('tiny_fontsizecolor', 'version'));
    }

    /**
     * A list an administrator customised is kept.
     */
    public function test_customised_sizes_are_kept(): void {
        $this->resetAfterTest();
        set_config('fontsizes', "10\n14\n24", 'tiny_fontsizecolor');

        $this->run_upgrade(2026092600);

        $this->assertSame("10\n14\n24", get_config('tiny_fontsizecolor', 'fontsizes'));
    }

    /**
     * A site that never saved the setting is left without one, so the default applies.
     */
    public function test_unset_sizes_stay_unset(): void {
        $this->resetAfterTest();
        unset_config('fontsizes', 'tiny_fontsizecolor');

        $this->run_upgrade(2026092600);

        $this->assertFalse(get_config('tiny_fontsizecolor', 'fontsizes'));
    }
}
