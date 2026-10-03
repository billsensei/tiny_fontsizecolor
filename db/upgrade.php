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
 * Upgrade steps for the Tiny font size and colour plugin.
 *
 * @package     tiny_fontsizecolor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_fontsizecolor\local\fontlist;

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion The version being upgraded from.
 * @return bool
 */
function xmldb_tiny_fontsizecolor_upgrade($oldversion) {
    if ($oldversion < 2026092700) {
        // The default size list changed from 8-18 to 8-12. Only replace it on sites that
        // still have the old default; a list an administrator has customised is kept.
        $sizes = get_config('tiny_fontsizecolor', 'fontsizes');
        if ($sizes !== false && fontlist::parse_fontsizes($sizes) === range(8, 18)) {
            set_config('fontsizes', fontlist::default_fontsizes_setting(), 'tiny_fontsizecolor');
        }

        upgrade_plugin_savepoint(true, 2026092700, 'tiny', 'fontsizecolor');
    }

    return true;
}
