# Translations

This folder contains the server's locale files (`eng.ini` is the source of truth).

## Editing translations

- Edit `eng.ini` for the English text shown by the server.
- Other language files override the strings they define; missing keys fall back to English.
- Placeholders use `%` syntax (e.g. `%0`, `%1`) and are filled in at runtime — do not remove them.

## Adding a new language

1. Copy `eng.ini` to the target locale code (three-letter code, e.g. `spa.ini`).
2. Translate the values (keep keys and placeholders untouched).
3. The language becomes selectable automatically if the client requests that locale.
