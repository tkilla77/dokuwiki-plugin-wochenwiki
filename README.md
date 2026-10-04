# Wochenwiki Plugin for DokuWiki

Helps maintain weekly class pages ("Wochenwiki").

Weeks are always ISO 8601 weeks (Monday to Sunday), and dates are always
written as `YYYY-MM-DD`.

## Syntax

On a line of its own:

    <woche 40/2026>
    <woche 2026-W40>
    <woche 2026-09-30>            any date → Monday of its ISO week
    <woche 40/2026 | Prüfung>     adds a suffix

renders the same header as `=== Woche 40 (2026-09-28) ===` (TOC entry,
anchor and section editing included).

### Reusing pages: weeks without year

    ~~SCHULJAHR:2026~~        (or ~~SCHULJAHR:2026/27~~, anywhere on the page)
    <woche 40>                → Woche 40 (2026-09-28)
    <woche 12>                → Woche 12 (2027-03-22)

Weeks after the `boundary` week (default 32) belong to the start year, all
others to the following year. To reuse the page next year, copy it and change
the directive: every `<woche N>` moves to the new school year. Without the
directive, `<woche N>` shows an error.

## Current week

On page view, the header of the current ISO week and its section get a
highlighted background. This also works for hand-written headers of the form
`Woche N (YYYY-MM-DD)`.

## Toolbar

The editor button inserts the current week, or, if that week is already on
the page, the week after the newest one: as `<woche N>` on pages with
`~~SCHULJAHR~~`, otherwise as `<woche N/YYYY>`.

## Configuration

* `label`: header text before the week number (default `Woche`)
* `level`: header level, 1–5 (default 4, i.e. `=== … ===`)
* `boundary`: last ISO week of the previous school year (default 32)

## Installation

Search for "wochenwiki" in the DokuWiki Extension Manager, or copy this folder
to `lib/plugins/wochenwiki`. Compatible with DokuWiki "Librarian" (2025-05-14)
and "Mort" (2026-07-14).

More: https://www.dokuwiki.org/plugin:wochenwiki

## License

GPL 2 (see [LICENSE](LICENSE)).
