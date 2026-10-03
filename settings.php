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
 * Admin settings for the Tiny font size and colour plugin.
 *
 * @package     tiny_fontsizecolor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_fontsizecolor\admin\setting_fontcolors;
use tiny_fontsizecolor\admin\setting_fontsizes;
use tiny_fontsizecolor\local\fontlist;

defined('MOODLE_INTERNAL') || die();

$plugin = 'tiny_fontsizecolor';

$settings = new admin_settingpage('tiny_fontsizecolor_settings', new lang_string('settings', $plugin));
if ($ADMIN->fulltree) {
    // Both lists are validated when saved, and again wherever they are read (see
    // classes/local/fontlist.php), so neither can inject arbitrary CSS into content.
    $settings->add(new setting_fontsizes(
        'tiny_fontsizecolor/fontsizes',
        get_string('fontsizes', $plugin),
        get_string('fontsizes_desc', $plugin, [
            'min' => fontlist::MIN_FONTSIZE,
            'max' => fontlist::MAX_FONTSIZE,
        ]),
        fontlist::default_fontsizes_setting()
    ));

    $settings->add(new setting_fontcolors(
        'tiny_fontsizecolor/fontcolors',
        get_string('fontcolors', $plugin),
        get_string('fontcolors_desc', $plugin),
        fontlist::default_fontcolors_setting()
    ));
}
