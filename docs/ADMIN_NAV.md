# Admin Sidebar (Config-driven)

The admin sidebar is driven by `config/admin_nav.php` and rendered dynamically in `resources/views/backend/partials/sidebar.blade.php`.

## Structure

`config/admin_nav.php` returns an array with a `buckets` key. Each bucket renders as a collapsible group in the sidebar. Items can be leaf links or nested groups.

Item fields:

- key: unique string id
- label_trans_key: translation key for the label (e.g. `menus.shipments`)
- route: route name for the item (e.g. `admin.shipments.index`)
- icon: Font Awesome class (e.g. `fa fa-box`)
- active_patterns: array of route name patterns used by `@navActive`, `@navShow`, `@navExpanded`
- permission_check: visibility guard as a compact string (see below)
- children: optional array of child items with the same shape

## Permission checks

We keep all existing ABAC and policies intact. Visibility is evaluated by `App\Support\Nav::canShowBySignature()` using a compact signature:

- `hasPermission:permission_key`
- `can:ability,Class\\Name` (policy check)
- Multiple rules can be OR-combined with `|`, e.g.: `hasPermission:api_keys_read|hasPermission:webhooks_read`
- `env:KEY,value` for simple env flag matching

If `permission_check` is omitted or `null`, the item is considered visible.

## Active/highlight

Provide `active_patterns` (route name patterns) so the Blade helpers can compute active/show/expanded:

- `@navActive($patterns)`
- `@navShow($patterns)`
- `@navExpanded($patterns)`

## Bucket state persistence

Each bucket’s collapsed/expanded state is persisted in `localStorage` using keys like `admin.sidebar.bucketState.<bucketKey>`. The state is restored on page load.

## Adding a new bucket or item

1. Add a new bucket or item to `config/admin_nav.php` with the required fields.
2. Reuse existing translation keys when possible, or add new keys under `lang/*/menus.php`.
3. Ensure the correct `permission_check` is set so that unauthorized users do not see the item.
4. Include `active_patterns` that cover the route(s) to enable proper highlight and expansion.

