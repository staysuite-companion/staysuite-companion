# Build & release

Two commands, both driven by `bin/lib.sh` (the single place where the release layout is defined).

```bash
bin/release.sh                # patch release: 1.0.0 -> 1.0.1
bin/release.sh --minor        # 1.0.0 -> 1.1.0
bin/release.sh --major        # 1.0.0 -> 2.0.0
bin/build.sh                  # production zip only
```

`npm run build:dist` and `npm run release` are thin aliases.

## `bin/build.sh`

```
bin/build.sh [version] [--lint] [--skip-npm] [--vendor=copy]
```

1. Verifies that the plugin header, `Plugin::VERSION` (`includes/Plugin.php`) and `readme.txt` `Stable tag:` all agree.
2. Optionally runs `composer lint` (`--lint`).
3. `npm ci` + `npm run build` (skipped with `--skip-npm`). `npm run build` compiles `src/scss` to `assets/build/css` first, then bundles the JS. It forces `NODE_ENV=development` for the install because a shell that exports `NODE_ENV=production` makes npm skip the devDependencies where `@wordpress/scripts` and `sass` live.
4. Fails if a compiled stylesheet is missing or older than its Sass source, so a `--skip-npm` build cannot ship stale styles.
5. Stages the production paths into a temp tree, runs `composer install --no-dev --optimize-autoloader` **inside the stage** (so `composer lint` still works locally afterwards), prunes editor cruft, zips into `dist/staysuite-companion-{version}.zip`.
6. Fails the build if any development path shows up in the zip listing, or if a compiled stylesheet did not make it in.

### Ships

`staysuite-companion.php`, `readme.txt`, `uninstall.php`, `LICENSE`, `includes/`, `templates/`, `assets/build/` (JS **and** `css/`, the Sass output), `assets/js/`, `assets/images/`, `languages/`, `vendor/` (production only). No `.scss` and no `.css` source ever ships.

### Never ships

`src/` (including `src/scss`), `node_modules/`, `docs/`, `bin/`, `dist/`, `plan/`, `CHANGELOG.d/`, `README.md`, `package*.json`, `composer*.json`, `webpack.config.js`, `phpcs.xml`, `.gitattributes`, `.scss`, source maps and `.DS_Store`.

The same list is mirrored in `.gitattributes` (`export-ignore`), so GitHub's source zip and `git archive` stay lean too. `bin/lib.sh` is the source of truth — add a path there and in `.gitattributes` together.

## `bin/release.sh`

```
bin/release.sh [--minor|--major|--set X.Y.Z] [--min-free X.Y.Z]
               [--notes "…"|--notes-file F] [--skip-npm]
               [--dry-run] [--no-push] [--no-gh] [--sync-only] [--allow-dirty] [--branch NAME]
```

**Version bump** — no flag means a patch (`1.0.0 → 1.0.1`), `--minor` raises the middle number and zeroes the last (`1.0.0 → 1.1.0`), `--major` raises the first and zeroes the rest (`1.0.0 → 2.0.0`). `--set X.Y.Z` sets an exact number. This is the only command in the repository that writes a version; `bin/build.sh` never touches one.

**Everywhere the version lives** — one command writes all of them, because each drift surfaces differently in the field:

| File | Marker |
|---|---|
| `staysuite-companion.php` | `Version:` header (Updates screen, wp.org) |
| `includes/Plugin.php` | `const VERSION` (own update checks; `SSC_VERSION` in the bootstrap is an alias) |
| `readme.txt` | `Stable tag:` (what wp.org actually serves) |
| `package.json` | `"version"` (tooling) |
| `readme.txt` | new `= x.y.z =` changelog entry |
| `../staysuite-companion-pro/*` | `MIN_FREE_VERSION` (`includes/Core/Pro.php`), `readme.txt`, `README.md` — with `--min-free` (the admin notice renders the constant dynamically) |
| `docs/pro.md` | the documented gate `>= x.y.z` — with `--min-free` |

`--min-free X.Y.Z` raises the free version the Pro add-on requires. It is opt-in rather than automatic: that constant is a *floor*, so raising it on every patch would force every Pro site to update the free plugin for no reason. Raise it when Pro starts using a hook or block that only exists in a new free release. Because Pro is a separate repository, the release prints its changed files at the end so they can be committed and released separately.

Order of operations:

1. **Preflight** — refuses a dirty tree (unless `--allow-dirty`), warns when releasing from a non-main branch, refuses a version that is not newer or a tag that already exists locally or on origin.
2. **Notes** — reads `CHANGELOG.d/{version}.md` when present, otherwise `--notes` / `--notes-file`. Bullets only; the heading is generated.
3. **Version sync** — writes the new version to every carrier above.
4. **Changelog** — inserts `= {version} =` above the newest entry in `readme.txt`, which is what wp.org renders on the plugin page. Previous entries are never rewritten.
5. **Build** — runs `bin/build.sh` with the new version.
6. **Publish** — `git add -A`, commit `Release staysuite-companion {version}`, annotated tag `v{version}`, push branch and tag, then `gh release create` with the zip attached.

`--dry-run` prints every step and writes nothing. `--sync-only` just reconciles the version files and validates them — handy after a manual header edit.

## Version policy

No flag is a patch release (bug fixes, no behaviour change). `--minor` is for new blocks, settings and hooks. `--major` is for breaking changes, renamed meta keys, or a data migration. Tags are `v{version}` (`v1.0.0`); the wp.org tag is the bare version.

## Commit style

The release script does not care how commits are worded, but Conventional Commits (`feat:`, `fix:`, `chore:`) make the changelog trivial to draft.

Two skills ship with the repository under `.claude/skills/` and automate the rest of the process:

- `wp-plugin-release` — what to commit, version placement, wp.org SVN publishing.
- `wp-changelog` — turning `git log v1.0.0..HEAD` into the `readme.txt` entry. Its `scripts/changelog-collect.sh` collects and groups the commits for you.

## wp.org publish

After approval the plugin lives in SVN. Layout:

```
trunk/                 <- plugin files, unzipped from dist/staysuite-companion-{version}.zip
tags/1.0.0/            <- svn cp trunk tags/1.0.0
assets/                <- contents of .wordpress-org/ (NOT inside trunk)
```

`.wordpress-org/` at the repo root is the staging area for the SVN
`assets/` directory: icons, banners and screenshots (`screenshot-N.png`
matching the readme captions). It is export-ignored from the GitHub zip
and never ships inside the plugin.

```bash
svn co https://plugins.svn.wordpress.org/staysuite-companion /tmp/ssc-svn
unzip -o dist/staysuite-companion-{version}.zip -d /tmp/ssc-build
cp -R /tmp/ssc-build/staysuite-companion/. /tmp/ssc-svn/trunk/
cp .wordpress-org/* /tmp/ssc-svn/assets/
cd /tmp/ssc-svn && svn add trunk assets --force
svn ci -m "Release {version} to trunk"
svn cp trunk tags/{version} && svn ci -m "Tag {version}"
```