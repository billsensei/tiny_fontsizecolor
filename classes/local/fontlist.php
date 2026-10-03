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

namespace tiny_fontsizecolor\local;

use core_text;

/**
 * Parsing, validation and defaults for the font size and font colour lists.
 *
 * This is the single source of truth for what counts as a valid size or colour. It is
 * used both when an administrator saves the settings and when the lists are sent to the
 * editor, so a hand-edited or corrupted config value can never reach the browser.
 *
 * @package     tiny_fontsizecolor
 * @copyright   2026 billsensei <wrwjpn@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fontlist {
    /** @var int Smallest font size, in points, that may be offered or applied. */
    public const MIN_FONTSIZE = 6;

    /** @var int Largest font size, in points, that may be offered or applied. */
    public const MAX_FONTSIZE = 72;

    /** @var string The unit is fixed because the feature is specified in points. */
    public const FONTSIZEUNIT = 'pt';

    /** @var int[] Font sizes offered until an administrator changes them. */
    public const DEFAULT_FONTSIZES = [8, 9, 10, 11, 12];

    /** @var int Maximum length of a colour label. */
    public const MAX_LABEL_LENGTH = 50;

    /** @var string[] Default colours, keyed by hex value, with the lang string id of their label. */
    private const DEFAULT_FONTCOLORS = [
        '#000000' => 'color_black',
        '#e03e2d' => 'color_red',
        '#e07c2d' => 'color_orange',
        '#2da44e' => 'color_green',
        '#1b6fc9' => 'color_blue',
        '#7e3ff2' => 'color_purple',
        '#ffffff' => 'color_white',
    ];

    /** @var string Strict 3 or 6 digit hex colour, e.g. #fff or #ffffff. */
    private const HEX_COLOR_PATTERN = '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i';

    /**
     * Default value of the font sizes setting.
     *
     * @return string One size per line.
     */
    public static function default_fontsizes_setting(): string {
        return implode("\n", self::DEFAULT_FONTSIZES);
    }

    /**
     * Default value of the font colours setting.
     *
     * @return string One "#rrggbb|Label" pair per line.
     */
    public static function default_fontcolors_setting(): string {
        $lines = [];
        foreach (self::DEFAULT_FONTCOLORS as $value => $stringid) {
            $lines[] = $value . '|' . get_string($stringid, 'tiny_fontsizecolor');
        }
        return implode("\n", $lines);
    }

    /**
     * Split a setting value into trimmed, non-empty lines.
     *
     * @param string $raw
     * @return string[]
     */
    private static function split_lines(string $raw): array {
        $lines = array_map('trim', preg_split('/\r\n|\r|\n/', $raw));
        return array_values(array_filter($lines, fn($line) => $line !== ''));
    }

    /**
     * Parse a single font size.
     *
     * @param string $value
     * @return int|null The size, or null if it is not a whole number within range.
     */
    public static function parse_fontsize(string $value): ?int {
        $value = trim($value);
        if (!ctype_digit($value)) {
            return null;
        }
        $size = (int) $value;
        if ($size < self::MIN_FONTSIZE || $size > self::MAX_FONTSIZE) {
            return null;
        }
        return $size;
    }

    /**
     * Parse a single hex colour, normalising it to lower case 6 digit form.
     *
     * @param string $value
     * @return string|null The colour, e.g. "#ff0000", or null if it is not a valid hex colour.
     */
    public static function parse_color(string $value): ?string {
        $value = strtolower(trim($value));
        if (!preg_match(self::HEX_COLOR_PATTERN, $value)) {
            return null;
        }
        if (strlen($value) === 4) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }
        return $value;
    }

    /**
     * Parse a font sizes setting value.
     *
     * @param string $raw One size per line.
     * @param string[] $invalid Receives every line that was rejected.
     * @return int[] Sorted, de-duplicated list of valid sizes.
     */
    public static function parse_fontsizes(string $raw, array &$invalid = []): array {
        $invalid = [];
        $sizes = [];
        foreach (self::split_lines($raw) as $line) {
            $size = self::parse_fontsize($line);
            if ($size === null) {
                $invalid[] = $line;
                continue;
            }
            $sizes[$size] = $size;
        }
        ksort($sizes);
        return array_values($sizes);
    }

    /**
     * Parse a font colours setting value.
     *
     * Any line that is not a well-formed "#hex|Label" (or bare "#hex") pair is rejected,
     * so this can never be used to smuggle arbitrary CSS into the editor content.
     *
     * @param string $raw One "#rrggbb|Label" pair per line.
     * @param string[] $invalid Receives every line that was rejected.
     * @param string[] $duplicates Receives every line repeating an earlier colour; the first one is kept.
     * @return array[] List of ['value' => '#rrggbb', 'label' => string], in the configured order.
     */
    public static function parse_fontcolors(string $raw, array &$invalid = [], array &$duplicates = []): array {
        $invalid = [];
        $duplicates = [];
        $colors = [];
        foreach (self::split_lines($raw) as $line) {
            $parts = explode('|', $line, 2);
            $value = self::parse_color($parts[0]);
            if ($value === null) {
                $invalid[] = $line;
                continue;
            }
            if (isset($colors[$value])) {
                $duplicates[] = $line;
                continue;
            }

            // Labels are rendered by TinyMCE as plain text, but clean them anyway in case a
            // future renderer changes that assumption. The pipe is the field separator.
            $label = clean_param(trim($parts[1] ?? ''), PARAM_TEXT);
            $label = trim(str_replace('|', '', core_text::substr($label, 0, self::MAX_LABEL_LENGTH)));
            if ($label === '') {
                $label = strtoupper($value);
            }

            $colors[$value] = ['value' => $value, 'label' => $label];
        }
        return array_values($colors);
    }

    /**
     * Format a list of sizes as a setting value.
     *
     * @param int[] $sizes
     * @return string
     */
    public static function format_fontsizes(array $sizes): string {
        return implode("\n", $sizes);
    }

    /**
     * Format a list of colours as a setting value.
     *
     * @param array[] $colors List of ['value' => '#rrggbb', 'label' => string].
     * @return string
     */
    public static function format_fontcolors(array $colors): string {
        return implode("\n", array_map(fn($color) => $color['value'] . '|' . $color['label'], $colors));
    }

    /**
     * The font sizes to offer in the editor.
     *
     * @return int[]
     */
    public static function get_fontsizes(): array {
        $raw = get_config('tiny_fontsizecolor', 'fontsizes');
        $sizes = ($raw === false) ? [] : self::parse_fontsizes($raw);
        return $sizes ?: self::DEFAULT_FONTSIZES;
    }

    /**
     * The font colours to offer in the editor.
     *
     * @return array[] List of ['value' => '#rrggbb', 'label' => string].
     */
    public static function get_fontcolors(): array {
        $raw = get_config('tiny_fontsizecolor', 'fontcolors');
        $colors = ($raw === false) ? [] : self::parse_fontcolors($raw);
        return $colors ?: self::parse_fontcolors(self::default_fontcolors_setting());
    }
}
