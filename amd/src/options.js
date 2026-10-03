// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * tiny_fontsizecolor for Moodle.
 *
 * @module      tiny_fontsizecolor/options
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const fontsizes = getPluginOptionName(pluginName, 'fontsizes');
const fontsizeunit = getPluginOptionName(pluginName, 'fontsizeunit');
const fontsizemin = getPluginOptionName(pluginName, 'fontsizemin');
const fontsizemax = getPluginOptionName(pluginName, 'fontsizemax');
const fontcolors = getPluginOptionName(pluginName, 'fontcolors');

/**
 * Register the options for the Tiny Font size and colour plugin.
 *
 * @param {TinyMCE} editor
 */
export const register = (editor) => {

    editor.options.register(fontsizes, {
        processor: 'array',
        "default": [],
    });

    editor.options.register(fontsizeunit, {
        processor: 'string',
        "default": 'pt',
    });

    editor.options.register(fontsizemin, {
        processor: 'number',
        "default": 6,
    });

    editor.options.register(fontsizemax, {
        processor: 'number',
        "default": 72,
    });

    editor.options.register(fontcolors, {
        processor: 'array',
        "default": [],
    });

};

/**
 * Get the list of font sizes, in points.
 *
 * @param {TinyMCE.editor} editor
 * @returns {Array} Array.
 */
export const getFontSizeList = (editor) => editor.options.get(fontsizes);

/**
 * Get the configured font size unit.
 *
 * @param {TinyMCE.editor} editor
 * @returns {string} The CSS unit to apply to font sizes.
 */
export const getFontSizeUnit = (editor) => editor.options.get(fontsizeunit);

/**
 * Get the smallest font size, in points, that may be applied.
 *
 * @param {TinyMCE.editor} editor
 * @returns {number}
 */
export const getFontSizeMin = (editor) => editor.options.get(fontsizemin);

/**
 * Get the largest font size, in points, that may be applied.
 *
 * @param {TinyMCE.editor} editor
 * @returns {number}
 */
export const getFontSizeMax = (editor) => editor.options.get(fontsizemax);

/**
 * Get the list of font colours.
 *
 * @param {TinyMCE.editor} editor
 * @returns {Array} Array of {value, label} objects.
 */
export const getFontColorList = (editor) => editor.options.get(fontcolors);
