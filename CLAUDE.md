# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A collection of phpBB 3.3.x extensions under the `booskit` vendor namespace. Each subdirectory of `booskit/` is a self-contained phpBB extension.

## Deployment (no build step)

Copy the `booskit/` directory into your phpBB installation's `ext/` directory, then enable each extension via the ACP (Admin Control Panel → Customise → Manage Extensions). There is no Composer install, no asset pipeline, and no test suite.

To apply database migrations after editing a migration file: disable then re-enable the extension in ACP, or use the phpBB CLI:
```
php bin/phpbbcli.php extension:migrate booskit/extensionname
```

## Extension anatomy

Every extension follows this layout:

```
booskit/{name}/
├── ext.php                  # Extension metadata class
├── composer.json            # Package name & phpBB version constraints
├── config/
│   ├── services.yml         # Symfony DI container definitions
│   └── routing.yml          # URL route definitions (if extension has HTTP endpoints)
├── acp/                     # Admin Control Panel module & info class
├── controller/              # HTTP controllers (called by routing.yml)
├── event/listener.php       # phpBB event subscriber (EventSubscriberInterface)
├── service/                 # Business logic managers
├── migrations/              # Versioned DB schema & data migrations
├── styles/{style}/template/ # Twig/phpBB HTML templates
├── styles/{style}/theme/    # CSS
└── language/en/             # Language string arrays
```

## Key patterns

**DI services** are declared in `config/services.yml` with IDs like `booskit.{ext}.controller.main`. Table name strings are injected as parameters (e.g. `%booskit.awards.tables.users%`).

**Events** — `event/listener.php` implements `EventSubscriberInterface`. `getSubscribedEvents()` maps phpBB core event names to handler methods. This is the primary hook mechanism; no monkey-patching.

**Migrations** — extend `\phpbb\db\migration\migration`. Implement `update_schema()` (DDL), `update_data()` (config keys, ACP modules), and matching `revert_*` methods. Name files `v100_initial.php`, `v101_add_foo.php`, etc. Always implement `effectively_installed()` to check for idempotency. All custom DB tables use the prefix `{table_prefix}booskit_{ext}_*`.

**Access control** — extensions that need group-based permissions store CSV group IDs in phpBB config keys and check them in the service layer. `forumprivacy` registers custom phpBB ACL permissions (`f_view_others_topics`, etc.) via the `core.permissions` event.

**API authentication** (`phpbbapi`) — reads `X-API-Key` header or `key` query param; compares to `booskit_phpbbapi_key` config with `hash_equals()`. Returns raw JSON via `header()`/`echo`/`exit` (no Symfony Response object).

**Post creation** (`forms`) — calls `submit_post()` from phpBB's `functions_posting.php`. When posting as another user it temporarily swaps `$user->data`, then restores it after the call.

## Extension index

| Extension | Purpose |
|-----------|---------|
| `phpbbapi` | Read-only JSON REST API (groups, users, forums). Auth via API key. |
| `gtawoauth` | OAuth login/account-linking with GTA:World UCP. |
| `datacollector` | Pushes user/thread data to external URL via POST (reverse API). |
| `awards` | User badge/award system with tiered access levels. |
| `disciplinary` | Disciplinary record management with group hierarchy. |
| `usercareer` | Career timeline displayed on user profiles. |
| `icdisciplinary` | In-character disciplinary records linked to characters. |
| `forms` | Custom form builder that auto-posts to a target forum. |
| `commendations` | Issue and display commendations on profiles. |
| `forumprivacy` | Per-forum privacy: users see only their own topics. |
| `postas` | Post under a character alias (aesthetic override). |
| `sendas` | Send PM as a character alias. |
| `gtawtracker` | Displays GTAW character data on forum profiles (requires `gtawoauth`). |
| `usercommandcenter` | Unified dashboard aggregating data from other extensions. |
