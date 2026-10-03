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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Base class for an admin setting holding one list entry per line.
 *
 * The value is stored as plain text, one entry per line, so the setting still works
 * as an ordinary textarea when JavaScript is unavailable. With JavaScript, the
 * tiny_fontsizecolor/admin_listeditor module replaces the textarea with a row editor
 * (with a colour picker for colours) that writes back into the hidden textarea.
 *
 * @package     tiny_fontsizecolor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class setting_list extends \admin_setting_configtextarea {
    /**
     * Constructor.
     *
     * @param string $name Unique ascii name, e.g. 'tiny_fontsizecolor/fontsizes'.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     * @param string $defaultsetting Default value, one entry per line.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_RAW, 60, 8);
    }

    /**
     * The list type understood by the admin_listeditor JavaScript module.
     *
     * @return string Either 'sizes' or 'colors'.
     */
    abstract protected function get_list_type(): string;

    /**
     * Parse a raw value.
     *
     * @param string $data Raw value, one entry per line.
     * @param string[] $invalid Receives every rejected line.
     * @param string[] $duplicates Receives every line that must not be repeated but was.
     * @return array Valid entries.
     */
    abstract protected function parse(string $data, array &$invalid, array &$duplicates): array;

    /**
     * Format valid entries as a stored value.
     *
     * @param array $entries Entries as returned by {@see self::parse()}.
     * @return string
     */
    abstract protected function format(array $entries): string;

    /**
     * Reject the value if it is empty, or any line is not a valid entry or is a duplicate.
     *
     * @param string $data
     * @return true|string True if valid, otherwise an error message.
     */
    public function validate($data) {
        $invalid = [];
        $duplicates = [];
        $entries = $this->parse((string) $data, $invalid, $duplicates);
        // No s() below: core_admin/setting escapes the error when rendering it.
        if (!empty($invalid)) {
            return get_string('error_invalidentries', 'tiny_fontsizecolor', implode(', ', $invalid));
        }
        if (!empty($duplicates)) {
            return get_string('error_duplicates', 'tiny_fontsizecolor', implode(', ', $duplicates));
        }
        if (empty($entries)) {
            // An empty list would silently fall back to the defaults in the editor.
            return get_string('error_empty', 'tiny_fontsizecolor');
        }
        return true;
    }

    /**
     * Validate and store the value in normalised form.
     *
     * @param string $data
     * @return string Empty string on success, otherwise an error message.
     */
    public function write_setting($data) {
        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }
        $invalid = [];
        $duplicates = [];
        $normalised = $this->format($this->parse((string) $data, $invalid, $duplicates));
        return $this->config_write($this->name, $normalised) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Render the textarea and attach the row editor to it.
     *
     * @param string $data
     * @param string $query
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        if (!$this->is_readonly()) {
            $PAGE->requires->js_call_amd('tiny_fontsizecolor/admin_listeditor', 'init', [
                $this->get_id(),
                $this->get_list_type(),
                fontlist::MIN_FONTSIZE,
                fontlist::MAX_FONTSIZE,
            ]);
        }
        return parent::output_html($data, $query);
    }
}
