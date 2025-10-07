# Translation Files

This directory contains translation files for the Peaches Bootstrap Ecwid Blocks plugin.

## Available Languages

- **English (en_US)**: Default language (source code)
- **Dutch (nl_NL)**: Complete translation

## File Types

- **`.pot`** - Portable Object Template file containing all translatable strings
- **`.po`** - Portable Object files containing translations for specific languages
- **`.mo`** - Machine Object files (compiled translations used by WordPress)

## For Translators

### Adding a New Language

1. Copy `peaches.pot` to `peaches-{locale}.po` (e.g., `peaches-fr_FR.po` for French)
2. Edit the header information in the new `.po` file
3. Translate all the `msgstr` entries
4. Compile the file: `msgfmt -o peaches-{locale}.mo peaches-{locale}.po`

### Updating Existing Translations

1. Update the `.po` file with new translations
2. Recompile: `msgfmt -o peaches-{locale}.mo peaches-{locale}.po`

## For Developers

### Updating the POT File

When new translatable strings are added to the codebase:

1. Run the extraction script: `npm run i18n:extract`
2. Update existing `.po` files: `npm run i18n:update`
3. Recompile all `.mo` files: `npm run i18n:compile`

### Text Domain

All strings use the text domain `peaches`.

### Translation Functions Used

- `__()` - Translate and return
- `_e()` - Translate and echo
- `_n()` - Translate with plural support
- `_x()` - Translate with context
- `esc_html__()` - Translate and escape for HTML
- `esc_attr__()` - Translate and escape for attributes

## Translation Coverage

The plugin includes translations for:

- Admin interface strings
- Block editor labels and descriptions
- Form fields and validation messages
- API endpoint descriptions
- Error messages and status updates
- Product-related terminology
- Navigation elements
- Settings and configuration options

## Contributing Translations

We welcome translations in additional languages! Please:

1. Fork the repository
2. Add your translation files following the naming convention
3. Test the translations in a WordPress environment
4. Submit a pull request with your changes

For questions about translations, please open an issue on GitHub.