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
 * Row editor for the font size and font colour admin settings.
 *
 * The setting is stored in a textarea, one entry per line. This module hides the
 * textarea and shows one row per entry instead (with a colour picker for colours),
 * writing every change back into the textarea so the normal admin form submits it.
 * The server validates the submitted value, so nothing here is a security boundary.
 *
 * @module      tiny_fontsizecolor/admin_listeditor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The snake_case export is used because getStrings only exists from Moodle 4.3.
import {get_strings as getStrings} from 'core/str';

const component = 'tiny_fontsizecolor';

// Placeholder substituted with the row number in per-row accessible labels.
const ROW = '__ROW__';

const HEX_COLOR_PATTERN = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;

const MAX_LABEL_LENGTH = 50;

/**
 * Normalise a hex colour to the 6 digit form an <input type="color"> accepts.
 *
 * @param {string} value
 * @returns {string|null} e.g. "#ff0000", or null if it is not a valid hex colour.
 */
const toColorInputValue = (value) => {
    if (!HEX_COLOR_PATTERN.test(value)) {
        return null;
    }
    const hex = value.slice(1).toLowerCase();
    return '#' + (hex.length === 3 ? hex.split('').map((c) => c + c).join('') : hex);
};

/**
 * Create an element with the given class and attributes.
 *
 * Values are only ever assigned as attributes or properties, never parsed as HTML.
 *
 * @param {string} tag
 * @param {string} className
 * @param {object} attributes
 * @returns {HTMLElement}
 */
const create = (tag, className, attributes = {}) => {
    const element = document.createElement(tag);
    element.className = className;
    Object.entries(attributes).forEach(([name, value]) => element.setAttribute(name, value));
    return element;
};

/**
 * Load the strings used by the editor.
 *
 * @returns {Promise<object>}
 */
const loadStrings = async() => {
    const keys = ['addsize', 'addcolor', 'fontsize', 'colorpicker', 'colorhex', 'colorlabel', 'remove'];
    const values = await getStrings(keys.map((key) => ({key, component, param: ROW})));
    return Object.fromEntries(keys.map((key, index) => [key, values[index]]));
};

/**
 * Build the inputs for one font size row.
 *
 * @param {string} line The stored line, e.g. "12".
 * @param {object} options
 * @param {number} options.min
 * @param {number} options.max
 * @returns {object} The row inputs, a serialiser, and the accessible label setters.
 */
const buildSizeRow = (line, {min, max}) => {
    // A line that is not a plain number (e.g. typed before this editor existed) is kept
    // in a text input so it is not silently lost; the server will then report it.
    const input = create('input', 'form-control d-inline-block w-auto mr-2 me-2', {
        type: /^\d*$/.test(line) ? 'number' : 'text',
        min: String(min),
        max: String(max),
        step: '1',
        size: '4',
    });
    input.value = line;
    const unit = create('span', 'mr-2 me-2');
    unit.textContent = 'pt';

    return {
        elements: [input, unit],
        serialise: () => input.value.trim(),
        setLabels: (strings, row) => input.setAttribute('aria-label', strings.fontsize.replace(ROW, row)),
    };
};

/**
 * Build the inputs for one font colour row.
 *
 * @param {string} line The stored line, e.g. "#ff0000|Red".
 * @returns {object} The row inputs, a serialiser, and the accessible label setters.
 */
const buildColorRow = (line) => {
    const separator = line.indexOf('|');
    const value = (separator === -1 ? line : line.slice(0, separator)).trim();
    const label = separator === -1 ? '' : line.slice(separator + 1).trim();

    const picker = create('input', 'form-control d-inline-block mr-2 me-2', {type: 'color'});
    picker.style.width = '3.5rem';
    picker.value = toColorInputValue(value) || '#000000';

    const hex = create('input', 'form-control d-inline-block w-auto mr-2 me-2', {
        type: 'text',
        size: '8',
        maxlength: '7',
        pattern: '#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})',
        spellcheck: 'false',
    });
    hex.value = value;

    const text = create('input', 'form-control d-inline-block w-auto mr-2 me-2', {
        type: 'text',
        size: '20',
        maxlength: String(MAX_LABEL_LENGTH),
    });
    text.value = label;

    // Keep the picker and the hex field in step with each other.
    picker.addEventListener('input', () => {
        hex.value = picker.value;
    });
    hex.addEventListener('input', () => {
        const color = toColorInputValue(hex.value.trim());
        if (color) {
            picker.value = color;
        }
    });

    return {
        elements: [picker, hex, text],
        serialise: () => {
            const labelValue = text.value.replace(/\|/g, '').trim();
            return labelValue === '' ? hex.value.trim() : `${hex.value.trim()}|${labelValue}`;
        },
        setLabels: (strings, row) => {
            picker.setAttribute('aria-label', strings.colorpicker.replace(ROW, row));
            hex.setAttribute('aria-label', strings.colorhex.replace(ROW, row));
            text.setAttribute('aria-label', strings.colorlabel.replace(ROW, row));
        },
    };
};

/**
 * Replace a list setting's textarea with a row editor.
 *
 * @param {string} textareaId The id of the setting's textarea.
 * @param {string} type Either 'sizes' or 'colors'.
 * @param {number} min Smallest allowed font size.
 * @param {number} max Largest allowed font size.
 */
export const init = async(textareaId, type, min, max) => {
    const textarea = document.getElementById(textareaId);
    if (!textarea || textarea.dataset.tinyFontsizecolorEditor) {
        return;
    }
    textarea.dataset.tinyFontsizecolorEditor = '1';

    const strings = await loadStrings();
    const [removeText] = await getStrings([{key: 'remove', component: 'core'}]);

    const list = create('ul', 'list-unstyled mb-2');
    const rows = [];

    const addButton = create('button', 'btn btn-secondary', {type: 'button'});
    addButton.textContent = type === 'colors' ? strings.addcolor : strings.addsize;

    // Accessible labels include the row number, so refresh them whenever rows change.
    const relabel = () => rows.forEach((row, index) => {
        row.setLabels(strings, index + 1);
        row.removeButton.setAttribute('aria-label', strings.remove.replace(ROW, index + 1));
    });

    const sync = () => {
        relabel();
        textarea.value = rows.map((row) => row.serialise()).filter((line) => line !== '').join('\n');
        // Let the form change checker know the form now has unsaved changes.
        textarea.dispatchEvent(new Event('change', {bubbles: true}));
    };

    const addRow = (line, focus = false) => {
        const row = type === 'colors' ? buildColorRow(line) : buildSizeRow(line, {min, max});
        const item = create('li', 'd-flex flex-wrap align-items-center mb-2');
        row.removeButton = create('button', 'btn btn-secondary', {type: 'button'});
        row.removeButton.textContent = removeText;
        row.removeButton.addEventListener('click', () => {
            rows.splice(rows.indexOf(row), 1);
            item.remove();
            sync();
            addButton.focus();
        });
        row.elements.forEach((element) => {
            element.addEventListener('input', sync);
            item.appendChild(element);
        });
        item.appendChild(row.removeButton);
        list.appendChild(item);
        rows.push(row);
        if (focus) {
            row.elements[0].focus();
        }
    };

    addButton.addEventListener('click', () => {
        // A new row starts empty, and is ignored until a value is chosen, so it can never
        // clash with an existing colour just by being added.
        addRow('', true);
        sync();
    });

    textarea.value.split(/\r\n|\r|\n/)
        .map((line) => line.trim())
        .filter((line) => line !== '')
        .forEach((line) => addRow(line));
    relabel();

    const container = create('div', 'tiny_fontsizecolor-listeditor');
    container.append(list, addButton);
    textarea.hidden = true;
    textarea.after(container);
};
