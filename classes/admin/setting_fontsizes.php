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

use tiny_fontsizecolor\local\fontlist;

/**
 * Admin setting for the list of font sizes offered in the editor.
 *
 * @package     tiny_fontsizecolor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_fontsizes extends setting_list {
    #[\Override]
    protected function get_list_type(): string {
        return 'sizes';
    }

    #[\Override]
    protected function parse(string $data, array &$invalid, array &$duplicates): array {
        // A repeated size carries no label, so it is simply merged rather than reported.
        $duplicates = [];
        return fontlist::parse_fontsizes($data, $invalid);
    }

    #[\Override]
    protected function format(array $entries): string {
        return fontlist::format_fontsizes($entries);
    }
}
