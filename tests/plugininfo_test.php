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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the plugin info class.
 *
 * @package     tiny_fontsizecolor
 * @category    test
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(plugininfo::class)]
final class plugininfo_test extends \advanced_testcase {
    /**
     * The button and menu item are the ones registered by the AMD module.
     */
    public function test_buttons_and_menuitems(): void {
        $this->assertSame(['tiny_fontsizecolor/plugin'], plugininfo::get_available_buttons());
        $this->assertSame(['tiny_fontsizecolor/plugin'], plugininfo::get_available_menuitems());
    }

    /**
     * Without any saved settings the editor receives the defaults and the size range.
     */
    public function test_configuration_defaults(): void {
        $this->resetAfterTest();
        unset_config('fontsizes', 'tiny_fontsizecolor');
        unset_config('fontcolors', 'tiny_fontsizecolor');

        $config = plugininfo::get_plugin_configuration_for_context(\context_system::instance(), [], []);

        $this->assertSame([8, 9, 10, 11, 12], $config['fontsizes']);
        $this->assertSame('pt', $config['fontsizeunit']);
        $this->assertSame(6, $config['fontsizemin']);
        $this->assertSame(72, $config['fontsizemax']);
        $this->assertCount(7, $config['fontcolors']);
        $this->assertSame('#000000', $config['fontcolors'][0]['value']);
    }

    /**
     * Saved settings are sent to the editor, and invalid lines never reach it.
     */
    public function test_configuration_uses_saved_settings(): void {
        $this->resetAfterTest();
        set_config('fontsizes', "20\n14\nabc\n500", 'tiny_fontsizecolor');
        set_config('fontcolors', "#F00|Alarm\nred; background:url(x)|Bad", 'tiny_fontsizecolor');

        $config = plugininfo::get_plugin_configuration_for_context(\context_system::instance(), [], []);

        $this->assertSame([14, 20], $config['fontsizes']);
        $this->assertSame([['value' => '#ff0000', 'label' => 'Alarm']], $config['fontcolors']);
    }

    /**
     * The plugin is only enabled for users who have the "use" capability.
     */
    public function test_is_enabled_requires_capability(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $options = ['pluginname' => 'fontsizecolor'];

        $this->assertTrue(plugininfo::is_enabled($context, $options, []));

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('tiny/fontsizecolor:use', CAP_PROHIBIT, $roleid, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertFalse(plugininfo::is_enabled($context, $options, []));
    }
}
