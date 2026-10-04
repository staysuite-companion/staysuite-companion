# AGENTS.md — StaySuite Companion for WpRentals

Instructions for AI coding agents (and humans) working in this repository.

## What this plugin is

A companion plugin for the [WpRentals](https://themeforest.net/item/wprentals-booking-accommodation-wordpress-theme/12332978) theme. It adds a **hotel layer** on top of the theme's existing listings (rooms): a `ssc_hotel` post type, five homepage blocks, and a group quote flow.

**The one rule that shapes everything: never modify a theme file.** Every override ships inside this plugin — templates via `PageTemplate`, styles built from `src/scss` into `assets/build/css`, scripts in `assets/build`. If something seems to need a theme edit, it needs a `theme_page_templates` filter, a `template_include` filter, or a `wp_add_inline_style` call instead.

## Commands

```bash
composer install          # dev deps (phpcs + WPCS)
npm install               # @wordpress/scripts + sass

composer lint             # PHPCS (WordPress standard)
vendor/bin/phpcbf         # auto-fix what can be fixed
npm run build             # dev build: Sass → assets/build/css, then JS → assets/build
npm run build:css         # Sass only, compressed (what ships)
npm run build:css:expanded  # Sass only, readable — for debugging compiled output
npm run watch:css         # rebuild styles on save; pair with npm start
npm run build:dist        # production zip → dist/

bin/build.sh              # minimal production zip
bin/release.sh            # patch release: 1.0.0 → 1.0.1
bin/release.sh --minor    # 1.0.0 → 1.1.0
bin/release.sh --major    # 0.3.0 → 1.0.0
bin/release.sh --dry-run  # show every step, change nothing
```

Two skills in `.claude/skills/` cover the release and changelog work in depth — read them before shipping: `wp-plugin-release`, `wp-changelog`.

Note: `npm run build` needs `NODE_ENV=development`. A shell that exports `NODE_ENV=production` makes npm skip devDependencies, where `@wordpress/scripts` and `sass` live, and the build fails confusingly. `bin/build.sh` forces it.

## Architecture

| Area | Files | Responsibility |
|---|---|---|
| Boot | `staysuite-companion.php` | Thin bootstrap: Composer autoload, `SSC_*` BC aliases, `boot()` on `plugins_loaded`, lifecycle hooks |
| Core | `includes/Plugin.php` | Singleton + container, class constants (`VERSION`, `MIN_PHP`, paths/URL), instantiation and hooks |
| Notices | `includes/Notice.php` | Every admin notice (PHP/theme checks, assets, AJAX dismissal) |
| Hotels | `includes/Hotel/` | `ssc_hotel` CPT, room↔hotel linking, queries |
| Blocks | `includes/Blocks/` | Block registration, server-side rendering, editor previews, patterns |
| Booking | `includes/Booking/` | Group request CPT, quote form, AJAX submit |
| Frontend | `includes/Frontend/` | Page template, template loader, scripts, theme integration |
| Admin | `includes/Admin/` | Settings, room assignment, term image/repair tools |
| Install | `includes/Installer.php` | Activation checks, homepage setup, DB flush |
| Styles | `src/scss/` | Sass source — one partial per feature, compiled to `assets/build/css` |
| UI assets | `assets/` | Compiled JS (`build/`), Sass output (`build/css/`), vanilla `js/`, theme logos |

Classes are namespaced `StaySuite\Companion\…` and autoloaded by classmap (`composer.json` → `includes/`). Instantiate them in `Plugin::instantiate()`; shared ones live in the container.

## Coding rules

- **`phpcs.xml` is the style authority — read it, do not guess.** `composer lint` runs it and must be clean before committing; `vendor/bin/phpcbf` fixes most of it. The ruleset is `WordPress-Extra` + `WordPress` (PHP 8.1+ and the minimum supported WP version are both checked), with these deliberate exceptions: 4-space indentation instead of tabs, no `in_array()` strictness excuse, no squizbling of short arrays or ternaries without reason, and `// phpcs:ignore` only with a `--` reason. PHPDoc on every class and method with `@package` and `@author`.
- **Namespace every hook** — these are the public seams the Pro add-on builds on, so renaming one is a breaking change. PHP actions and filters take `ssc_` plus a descriptive name (`ssc_room_card_actions`, `ssc_quote_payload`, `ssc_group_request_saved`); the admin tab registry is a JS filter, `ssc.admin.tabs`. Shortcodes are `ssc_*`, blocks `ssc/*`, REST routes `ssc/v1/*`. Document every new one in `docs/hooks.md`.
- **Prefixes:** `ssc_` functions and meta (`_ssc_`), `SSC_` constants (`SSC_VERSION`, `SSC_PATH`, `SSC_URL`). Text domain `staysuite-companion`.
- **Escape on output** (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`), **prepare on input** (`$wpdb->prepare` for every query, `sanitize_*` for every field), **nonce every** AJAX and REST route.
- **Translate every user-facing string** with the text domain, including admin notices and JS (`wp.i18n` + `wp_set_script_translations`).
- **SQL:** use `$wpdb->prepare`. Direct queries need a `phpcs:disable WordPress.DB.DirectDatabaseQuery` comment explaining why.
- **Never enqueue from a CDN.** Register scripts and styles with the plugin, with explicit dependencies and version `SSC_VERSION`.
- **JS/React:** `@wordpress/scripts`, components in `src/`, output in `assets/build/`. Use `wp.element`, the `@wordpress/*` packages and `wp.apiFetch` — no jQuery, no new runtime dependencies. Each screen gets its own script handle in `Frontend/Scripts.php`.
- **Styles are Sass only — never add or edit a `.css` file.** `src/scss/ssc-hotel.scss` and `src/scss/ssc-admin.scss` are the sources; `assets/build/css/*.css` is generated output. Edit a partial, run `npm run build:css`, and commit the compiled file alongside the source. Colours and spacing stay CSS custom properties (the customizer overrides them at runtime); only breakpoints are Sass variables, in `src/scss/_breakpoints.scss`. The stylesheet is one flat cascade, so `@use` order in the entry file is load-bearing — a later partial may deliberately override an earlier one. Enqueue paths go through the `Scripts::HOTEL_STYLE` and `Settings::ADMIN_STYLE` constants, never a literal path.
- **Blocks** are dynamically registered in `Blocks/Registry.php` with server-side rendering; editor previews go through `ssc/v1/preview`.

## Data and compatibility

- Post types: `ssc_hotel`, `ssc_group_request`. Listings (rooms) are the theme's own posts — never copy or duplicate theme data.
- Meta: `_ssc_*`. Renaming a meta key needs a data migration routine and a `--major` release.
- Theme integration is defensive: guard every theme function call with `function_exists()` so a theme update cannot fatal the site.
- Read availability from the theme (`wpestate_check_booking_valability`), never re-implement booking.

## Boundaries with the Pro add-on

- **No license, updater or payment code in this repository.** wordpress.org reviewers reject it, and Pro must work without touching free.
- Pro hooks free's public seams and writes only `_ssc_pro_*` meta. When you add a seam Pro might need, add it as an action/filter that passes the data Pro needs, and document it in `docs/hooks.md`.
- The free version this plugin requires of Pro is the other way around: Pro's `MIN_FREE_VERSION`. Raise it from the free side with `bin/release.sh --min-free X.Y.Z` when Pro starts depending on a new hook here.

## Release rules

- **`bin/release.sh` is the only thing that writes a version.** Never hand-edit `Version:`, `SSC_VERSION`, `Stable tag:` or `package.json` in a release commit — the script syncs all of them and fails the build when they disagree.
- `--dry-run` first, always. It is free and catches everything the real run would do.
- Changelog bullets live in `CHANGELOG.d/{version}.md` and are inserted into `readme.txt` by the script. Write them for a site owner: what changed for them, not which component changed.
- `dist/` is git-ignored. Never commit a zip.
- wp.org publication happens after the GitHub release: trunk, then `svn cp trunk tags/{version}` — see `docs/release.md`.

## Before you finish

1. `composer lint` — new violations are not acceptable.
2. `npm run build` — the committed `assets/build/` must match `src/`, including `assets/build/css/` from `src/scss/`.
3. `bin/build.sh` — confirm the zip contains only runtime files and still activates.
4. Update `docs/` when behaviour or a hook changes; update `readme.txt` only when the plugin page should say something new.
5. Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`) — the changelog tooling reads them.