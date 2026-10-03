# moodle-tiny_fontsizecolor

A [TinyMCE](https://www.tiny.cloud/) editor plugin for Moodle that adds a font size picker and a font colour picker to the toolbar and Format menu, letting users change the size and colour of selected text.

## Features

- Toolbar buttons and Format menu entries for choosing a font size and a font colour
- Font sizes are whole points between 6pt and 72pt. By default the picker offers 8, 9, 10, 11 and 12pt; administrators can edit, add or remove sizes
- Administrators manage the colour list with a colour picker per colour, plus an optional label
- The size and colour menus tick the entry that matches the selected text
- **Default size** and **Remove colour** entries return the selection to the default size or colour
- Every user can also apply a one-off size with **Custom size...** or any colour with **Custom colour...** (a colour picker) in the editor
- Everything is validated on the server when saved and again before it reaches the editor: only whole-number sizes in range and strict hex colours are accepted, so the settings cannot inject arbitrary CSS
- No third-party libraries, external services, or additional Moodle plugins are required - it works on a standard Moodle installation

## Requirements

- Moodle 4.1 (2022112800) or later

## Installation

Copy (or clone) this repository into your Moodle installation at:

```
lib/editor/tiny/plugins/fontsizecolor
```

(On Moodle 5.1 and later, which use a `public` directory: `public/lib/editor/tiny/plugins/fontsizecolor`.)

Then visit *Site administration &raquo; Notifications* to complete the installation.

## Settings

Go to *Site administration &raquo; Plugins &raquo; Text editor &raquo; TinyMCE editor &raquo; Font size and colour* to configure:

| Setting | Description |
| --- | --- |
| Font sizes | The sizes (in points, 6-72) offered in the picker. Default: 8, 9, 10, 11, 12 |
| Font colours | The colours offered in the picker, each with a colour picker and an optional label |

Both settings are shown as editable rows with **Add size** / **Add colour** and **Remove** buttons. Without JavaScript they fall back to a plain text box with one entry per line (`12` for sizes, `#rrggbb|Label` for colours).

Upgrading from 1.0.x: if the font size list was still the old default (8-18), it is replaced by the new default (8-12). A customised list is kept.

## Capability

Access to the buttons and menu items is controlled by `tiny/fontsizecolor:use`, which is allowed for all authenticated users by default. Remove it from a role to hide the plugin from users with that role.

## Changelog

- **1.2.0** - Added **Default size** and **Remove colour** entries, and ticks showing the current size and colour in the menus. The Cancel and Save buttons of the custom dialogs now use Moodle's translated strings. Added PHPUnit tests for the plugin info, upgrade step and privacy provider, and a Behat test for the menus. PHPUnit tests use attributes instead of `@covers` doc-comments.
- **1.1.0** - Default size list changed from 8-18pt to 8-12pt; custom size and colour dialogs; list editors in the admin settings.

## License

Licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).
# tiny_smaller_fonts
# tiny_fontsizecolor
