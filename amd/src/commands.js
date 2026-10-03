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
 * Commands helper for the Moodle tiny_fontsizecolor plugin.
 *
 * @module      tiny_fontsizecolor/commands
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getFontColorList, getFontSizeList, getFontSizeMax, getFontSizeMin, getFontSizeUnit} from './options';
import {getButtonImage} from 'editor_tiny/utils';
// The snake_case exports are used because getString/getStrings only exist from Moodle 4.3.
import {get_string as getString, get_strings as getStrings} from 'core/str';
import {
    component,
    fontcolorButtonName,
    fontcolorIcon,
    fontcolorMenuItemName,
    fontsizeButtonName,
    fontsizeIcon,
    fontsizeMenuItemName,
} from './common';

// A strict 3 or 6 digit hex colour, e.g. #fff or #ffffff. Anything else is refused
// rather than being passed through to the editor's inline style, which is what keeps
// the colour picker from being usable to inject arbitrary CSS into the document.
const HEX_COLOR_PATTERN = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;

// Used for the custom colour dialog when the selection has no readable colour.
const FALLBACK_COLOR = '#000000';

// TinyMCE's own "fontsize" format only removes a size that matches the value given, so this
// format (registered per editor) has remove_similar to remove any size from the selection.
const FONTSIZE_RESET_FORMAT = 'tiny_fontsizecolor_fontsize_reset';

/**
 * Parse a font size, accepting only whole numbers within the configured range.
 *
 * This mirrors the server side validation in classes/local/fontlist.php, and is what
 * validates sizes typed into the custom size dialog.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @param {number|string} value
 * @returns {number|null} The size, or null if it is not valid.
 */
const parseFontSize = (editor, value) => {
    const text = String(value).trim();
    if (!/^\d+$/.test(text)) {
        return null;
    }
    const size = parseInt(text, 10);
    if (size < getFontSizeMin(editor) || size > getFontSizeMax(editor)) {
        return null;
    }
    return size;
};

/**
 * Normalise a hex colour to lower case 6 digit form.
 *
 * @param {string} value e.g. "#F00", "f00" or "#ff0000".
 * @returns {string|null} e.g. "#ff0000", or null if it is not a valid hex colour.
 */
const parseColor = (value) => {
    if (typeof value !== 'string') {
        return null;
    }
    let color = value.trim().toLowerCase();
    if (!color.startsWith('#')) {
        color = `#${color}`;
    }
    if (!HEX_COLOR_PATTERN.test(color)) {
        return null;
    }
    if (color.length === 4) {
        color = '#' + color.slice(1).split('').map((c) => c + c).join('');
    }
    return color;
};

/**
 * Apply a font size to the current selection.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @param {number} fontsize Font size in points.
 */
const handleFontSizeAction = (editor, fontsize) => {
    const size = parseFontSize(editor, fontsize);
    if (size === null) {
        return;
    }
    editor.undoManager.transact(() => {
        editor.focus();
        editor.formatter.apply('fontsize', {value: size + getFontSizeUnit(editor)});
        editor.nodeChanged();
    });
};

/**
 * Apply a font colour to the current selection.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @param {string} color Hex colour, e.g. "#ff0000".
 */
const handleFontColorAction = (editor, color) => {
    const value = parseColor(color);
    if (value === null) {
        return;
    }
    editor.undoManager.transact(() => {
        editor.focus();
        editor.formatter.apply('forecolor', {value});
        editor.nodeChanged();
    });
};

/**
 * Remove the font size from the current selection, returning it to the default size.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 */
const handleResetFontSizeAction = (editor) => {
    editor.undoManager.transact(() => {
        editor.focus();
        editor.formatter.remove(FONTSIZE_RESET_FORMAT);
        editor.nodeChanged();
    });
};

/**
 * Remove the font colour from the current selection, returning it to the default colour.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 */
const handleResetFontColorAction = (editor) => {
    editor.undoManager.transact(() => {
        editor.focus();
        editor.formatter.remove('forecolor', {value: null}, undefined, true);
        editor.nodeChanged();
    });
};

/**
 * The font size of the selection, in points, if it is set in points.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @returns {string} e.g. "14", or an empty string.
 */
const getCurrentFontSize = (editor) => {
    const unit = getFontSizeUnit(editor);
    const value = String(editor.queryCommandValue('FontSize') || '');
    if (!value.endsWith(unit)) {
        return '';
    }
    const size = parseFontSize(editor, value.slice(0, -unit.length));
    return size === null ? '' : String(size);
};

/**
 * The text colour of the selection, as a hex colour.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @returns {string} e.g. "#ff0000".
 */
const getCurrentColor = (editor) => {
    const color = editor.dom.getStyle(editor.selection.getStart(), 'color', true) || '';
    const rgb = color.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
    if (rgb) {
        return '#' + rgb.slice(1, 4).map((c) => Math.min(255, parseInt(c, 10)).toString(16).padStart(2, '0')).join('');
    }
    return parseColor(color) || FALLBACK_COLOR;
};

/**
 * Open a dialog asking for a font size, and apply it to the selection.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @param {object} strings Localised strings.
 */
const openCustomSizeDialog = (editor, strings) => {
    editor.windowManager.open({
        title: strings.customSizeTitle,
        size: 'normal',
        body: {
            type: 'panel',
            items: [{
                type: 'input',
                name: 'fontsize',
                inputMode: 'numeric',
                label: strings.customSizeLabel,
            }],
        },
        buttons: [
            {type: 'cancel', name: 'cancel', text: strings.cancel},
            {type: 'submit', name: 'save', text: strings.save, primary: true},
        ],
        initialData: {fontsize: getCurrentFontSize(editor)},
        onSubmit: (api) => {
            const size = parseFontSize(editor, api.getData().fontsize);
            if (size === null) {
                editor.windowManager.alert(strings.customSizeInvalid);
                return;
            }
            api.close();
            handleFontSizeAction(editor, size);
        },
    });
};

/**
 * Open a colour picker dialog, and apply the chosen colour to the selection.
 *
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 * @param {object} strings Localised strings.
 */
const openCustomColorDialog = (editor, strings) => {
    editor.windowManager.open({
        title: strings.customColorTitle,
        size: 'normal',
        body: {
            type: 'panel',
            items: [{
                type: 'colorpicker',
                name: 'colorpicker',
                label: strings.customColorTitle,
            }],
        },
        buttons: [
            {type: 'cancel', name: 'cancel', text: strings.cancel},
            {type: 'submit', name: 'save', text: strings.save, primary: true},
        ],
        initialData: {colorpicker: getCurrentColor(editor)},
        onSubmit: (api) => {
            const color = parseColor(api.getData().colorpicker);
            if (color === null) {
                // The picker flags an invalid hex value itself; keep the dialog open.
                return;
            }
            api.close();
            handleFontColorAction(editor, color);
        },
    });
};

/**
 * Build the small coloured square icon used for a font colour menu item.
 *
 * The colour has already been normalised by parseColor() by the caller, so it is
 * safe to interpolate directly into the SVG markup.
 *
 * @param {string} color Hex colour, e.g. "#ff0000".
 * @returns {string} SVG markup.
 */
const getColorSwatchIcon = (color) =>
    `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">` +
    `<rect x="4" y="4" width="16" height="16" fill="${color}" stroke="#8a8a8a" stroke-width="1"/>` +
    `</svg>`;

/**
 * Get the setup function for the buttons.
 *
 * This is performed in an async function which ultimately returns the registration function as the
 * Tiny.AddOnManager.Add() function does not support async functions.
 *
 * @returns {function} The registration function to call within the Plugin.add function.
 */
export const getSetup = async() => {
    const [
        [
            fontsizeButtonNameTitle,
            fontsizeMenuItemNameTitle,
            fontcolorButtonNameTitle,
            fontcolorMenuItemNameTitle,
            customSizeText,
            customSizeTitle,
            customColorText,
            customColorTitle,
            resetSizeText,
            resetColorText,
            cancelText,
            saveText,
        ],
        fontsizeButtonImage,
        fontcolorButtonImage,
    ] = await Promise.all([
        getStrings([
            'button_fontsize',
            'menuitem_fontsize',
            'button_fontcolor',
            'menuitem_fontcolor',
            'customsize',
            'customsize_title',
            'customcolor',
            'customcolor_title',
            'resetsize',
            'resetcolor',
        ].map((key) => ({key, component})).concat([
            {key: 'cancel', component: 'core'},
            {key: 'savechanges', component: 'core'},
        ])),
        getButtonImage(fontsizeIcon, component),
        getButtonImage(fontcolorIcon, component),
    ]);

    return (editor) => {
        // Strings that depend on this editor's configured size range.
        const range = {min: getFontSizeMin(editor), max: getFontSizeMax(editor)};
        const rangeStrings = Promise.all([
            getString('customsize_label', component, range),
            getString('customsize_invalid', component, range),
        ]);
        const buttonStrings = {cancel: cancelText, save: saveText};
        const openSizeDialog = () => rangeStrings.then(([customSizeLabel, customSizeInvalid]) =>
            openCustomSizeDialog(editor, {...buttonStrings, customSizeTitle, customSizeLabel, customSizeInvalid}));
        const openColorDialog = () => openCustomColorDialog(editor, {...buttonStrings, customColorTitle});

        editor.on('PreInit', () => {
            editor.formatter.register(FONTSIZE_RESET_FORMAT, {
                inline: 'span',
                styles: {fontSize: '%value'},
                'remove_similar': true,
                'clear_child_styles': true,
            });
        });

        // Register the Moodle SVG icons as icons suitable for use as TinyMCE toolbar buttons.
        editor.ui.registry.addIcon(fontsizeIcon, fontsizeButtonImage.html);
        editor.ui.registry.addIcon(fontcolorIcon, fontcolorButtonImage.html);

        // Font size: button, menu item, and their shared submenu.
        const unit = getFontSizeUnit(editor);
        const fontSizeList = getFontSizeList(editor)
            .map((size) => parseFontSize(editor, size))
            .filter((size) => size !== null);

        // The submenus are built each time they open, so the item matching the selection is ticked.
        const getFontSizeSubmenuItems = () => {
            const current = getCurrentFontSize(editor);
            const items = fontSizeList.map((size) => ({
                type: 'togglemenuitem',
                text: `${size} ${unit}`,
                active: String(size) === current,
                onAction: () => handleFontSizeAction(editor, size),
            }));
            if (items.length) {
                items.push({type: 'separator'});
            }
            items.push({
                type: 'menuitem',
                text: customSizeText,
                onAction: openSizeDialog,
            }, {
                type: 'menuitem',
                text: resetSizeText,
                onAction: () => handleResetFontSizeAction(editor),
            });
            return items;
        };

        editor.ui.registry.addNestedMenuItem(fontsizeMenuItemName, {
            icon: fontsizeIcon,
            text: fontsizeMenuItemNameTitle,
            getSubmenuItems: getFontSizeSubmenuItems,
        });

        editor.ui.registry.addMenuButton(fontsizeButtonName, {
            icon: fontsizeIcon,
            tooltip: fontsizeButtonNameTitle,
            fetch: (callback) => callback(getFontSizeSubmenuItems()),
        });

        // Font colour: button, menu item, and their shared submenu.
        const fontColorList = getFontColorList(editor)
            .map((entry) => ({value: parseColor(entry?.value), label: String(entry?.label ?? '')}))
            .filter((entry) => entry.value !== null);

        fontColorList.forEach((entry) => {
            editor.ui.registry.addIcon(`${fontcolorIcon}_${entry.value}`, getColorSwatchIcon(entry.value));
        });

        const getFontColorSubmenuItems = () => {
            const current = getCurrentColor(editor);
            const items = fontColorList.map((entry) => ({
                type: 'togglemenuitem',
                icon: `${fontcolorIcon}_${entry.value}`,
                text: entry.label,
                active: entry.value === current,
                onAction: () => handleFontColorAction(editor, entry.value),
            }));
            if (items.length) {
                items.push({type: 'separator'});
            }
            items.push({
                type: 'menuitem',
                icon: 'color-picker',
                text: customColorText,
                onAction: openColorDialog,
            }, {
                type: 'menuitem',
                text: resetColorText,
                onAction: () => handleResetFontColorAction(editor),
            });
            return items;
        };

        editor.ui.registry.addNestedMenuItem(fontcolorMenuItemName, {
            icon: fontcolorIcon,
            text: fontcolorMenuItemNameTitle,
            getSubmenuItems: getFontColorSubmenuItems,
        });

        editor.ui.registry.addMenuButton(fontcolorButtonName, {
            icon: fontcolorIcon,
            tooltip: fontcolorButtonNameTitle,
            fetch: (callback) => callback(getFontColorSubmenuItems()),
        });
    };
};
