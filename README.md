# StaySuite Companion for WpRentals

Hotels, homepage booking blocks and group quotes for the [WpRentals](https://themeforest.net/item/wprentals-booking-accommodation-wordpress-theme/12332978) theme — without touching theme files.

![License](https://img.shields.io/badge/license-GPL--3.0--or--later-blue) ![Requires PHP](https://img.shields.io/badge/php-%3E%3D8.1-777) ![WP](https://img.shields.io/badge/wordpress-%3E%3D6.0-21759B)

## What it does

| Area | Details |
|---|---|
| **Hotels** | `ssc_hotel` post type linked to listings (rooms): gallery cover, auto-aggregated facilities, date strip, availability badges, glass room cards with theme sliders, map |
| **Homepage blocks** | `ssc/hero-search`, `ssc/term-tablets`, `ssc/listing-carousel`, `ssc/payment-strip`, `ssc/group-booking` (+ `ssc_*` shortcodes) |
| **Group booking** | Individual/Group capsule on the homepage search; group mode sends a combined quote (stored + emailed) |
| **Theme-native** | Reuses the theme's search, datepickers, guest panels, sliders and maps; colors track the customizer |

Full user docs live in [`docs/`](docs/README.md). The wp.org listing file is [`readme.txt`](readme.txt).

## Develop

```bash
npm install
npm run build          # Sass → assets/build/css, then wp-scripts → assets/build
npm run build:css      # styles only (compressed, what ships)
npm run watch:css      # rebuild styles on save; pair with npm start
```

PHP follows WordPress coding standards with PHPDoc everywhere; JS is React via `@wordpress/scripts`; styles are **Sass only** — `src/scss` is the source, `assets/build/css` is generated, and there is no hand-written `.css` in the repository. No theme file is ever modified — overrides ship as plugin templates, styles and scripts. See [`docs/customization.md`](docs/customization.md) for the stylesheet workflow.

## Release

```bash
bin/release.sh             # patch: 1.0.0 -> 1.0.1
bin/release.sh --minor     # 1.0.0 -> 1.1.0
bin/release.sh --major     # 1.0.0 -> 2.0.0
bin/build.sh               # minimal production zip → dist/
```

`bin/release.sh` is the only command that writes a version; it syncs the header, `Plugin::VERSION` (`includes/Plugin.php`), `readme.txt` `Stable tag:`, `package.json` and the changelog in one pass, then commits, tags `v{version}`, pushes and attaches the zip to the GitHub release. `--min-free X.Y.Z` raises the free version the Pro add-on requires, rewriting its gate constant, admin notice, readmes and `docs/pro.md`. Full process, including the wp.org SVN publish: [`docs/release.md`](docs/release.md).

## License

GPL-3.0-or-later. © Tanmay Kirtania — [jktanmay.com](https://jktanmay.com)
