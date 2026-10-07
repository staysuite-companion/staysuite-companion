# Customization

## Theme color tracking

Submit buttons, hovers and search icons are **not hardcoded**. They are emitted per load from the theme customizer into CSS variables — recolor the theme and the plugin follows on next load:

| Variable | Source |
|---|---|
| `--ssc-accent` | `wp_estate_main_color` |
| `--ssc-accent-hover` | `wp_estate_hover_button_color` |
| `--ssc-text` / `--ssc-headings` | font / headings colors |
| `--ssc-submit`, `--ssc-submit-hover`, `--ssc-search-icon` | accent + hover (search bars) |

## Body classes

* `ssc-homepage` — StaySuite Homepage template (transparent header, capsule, dividers).
* `ssc-has-hero` — a hero block is present (header search suppressed, hero calendar binding).
* `ssc-invoice-page` — branded invoice template (invoices, mockup data until the quote-to-invoice pipeline ships).
* `single-ssc_hotel` — hotel singles (theme map header suppressed).

## Stylesheet

Styles are **Sass**. `src/scss/ssc-hotel.scss` is the only stylesheet source; it `@use`s one partial per feature from `src/scss/partials/` and compiles to `assets/build/css/ssc-hotel.css`, cache-busted by content hash so edits land on a normal reload. There is no hand-written `.css` in the repository — never edit the compiled file, and never add a new one.

```bash
npm run build:css           # compressed, what ships
npm run build:css:expanded  # readable output for debugging a compiled file
npm run watch:css           # rebuild on save; pair with npm start
```

`npm run build` compiles the stylesheets before the JS, and `bin/build.sh` refuses to package a zip whose compiled CSS is missing or older than its Sass source. All rules are `.ssc-`-prefixed; the theme is never overridden globally. Breakpoints live in `src/scss/_breakpoints.scss`; colours stay CSS custom properties so the customizer can override them at runtime.
