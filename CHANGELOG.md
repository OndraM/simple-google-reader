# Changelog

<!-- There is always Unreleased section on the top. Subsections (Added, Changed, Fixed, Removed) should be added as needed. -->

## Unreleased

## 1.0.1 - 2024-12-23
- Fix PHP 8.4 deprecation notices.
- Fix incorrect phpDoc typehint for `SpreadsheetsReader::readById()`.

## 1.0.0 - 2024-03-14
- **[BC break]** Rename `Reader` to `SpreadsheetsReader`.
- Feat: Add Google Docs Reader, with ability to read documents either as plaintext or as HTML.
- Feat: Skip empty rows when reading spreadsheets.
- Feat: Add integration tests and run them on GitHub Actions.
- Fix: Do not throw fatal error when reading empty table.
- Docs: Extend README and clarify steps to obtain credentials.

## 0.3.0 - 2024-02-28
- Add option to set cache TTL per request.

## 0.2.0 - 2024-01-29
- Require PHP^8.1.

## 0.1.0 - 2024-01-29
- Initial release.
