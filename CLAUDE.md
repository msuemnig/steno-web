# Steno Web — Claude context

## Local dev setup

- PHP is served by **Laravel Herd** — never suggest `php artisan serve`
- App URL: `https://steno-web.test`
- Vite dev server: `npm run dev`
- Tests: `php artisan test` (requires `steno_test` database to exist)

## Key architectural decisions

- **Team is the billable entity** — Cashier is on `Team`, not `User`. Always check `$team->subscribed('default')`, never `$user->subscribed()`
- **UUID PKs on sites/personas/scripts** — Client-generated in the extension for offline-first. Never generate IDs server-side for these models.
- **Sync requires subscription** — `POST /api/sync` is gated by `EnsureActiveSubscription` middleware. Free users get 2 scripts max via `ApiScriptController`.
- **Last-write-wins sync** — `SyncController` compares `updated_at` timestamps. Always preserve `updated_at` from the winning side.
- **Soft deletes propagate via sync** — `withTrashed()` is used everywhere in sync so deletions travel between devices.

## Plan config

Plan limits live in `config/steno.php` — check there before hardcoding any limits.

## Companion repo

The browser extension is in a sibling directory: `../steno` (GitHub: `msuemnig/steno`).

## Version bumping

When making extension changes, bump the version in `../steno/manifest.json`. Web app has no equivalent version file.

## Issues

All todos and tasks are tracked at: https://github.com/msuemnig/steno-web/issues
