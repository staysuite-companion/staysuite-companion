#!/usr/bin/env bash
#
# Shared configuration and helpers for bin/build.sh and bin/release.sh.
#
# Sourced, never executed directly. Every release-relevant decision lives here:
# which paths ship, how the version is read and written, and how the staging
# tree is assembled. Change the release layout in one place.
#
# @package StaySuite\Companion

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="staysuite-companion"
MAIN_FILE="staysuite-companion.php"
README_FILE="readme.txt"
# Runtime version constant, kept in sync with the header for update checks.
# The source of truth is the Plugin class constant; the bootstrap only
# defines a dynamic SSC_VERSION alias, so the tooling reads/writes the class.
VERSION_CONST="VERSION"
CLASS_VERSION_FILE="includes/Plugin.php"
PACKAGE_FILE="package.json"

# Everything users need at runtime. Nothing else is copied into the zip.
# Styles live in assets/build/css — Sass output from src/scss, built by
# `npm run build:css`. There is no hand-written CSS to ship.
PRODUCTION_PATHS=(
    "$MAIN_FILE"
    "$README_FILE"
    "uninstall.php"
    "LICENSE"
    "includes"
    "templates"
    "assets/build"
    "assets/js"
    "assets/images"
    "languages"
)

# Composer dev packages, as vendor/<name> paths. Only used by the
# --vendor=copy fallback when composer cannot produce a --no-dev tree.
DEV_VENDOR_PATHS=(
    "vendor/squizlabs"
    "vendor/wp-coding-standards"
    "vendor/phpcsstandards"
    "vendor/phpcompatibility"
    "vendor/dealerdirect"
    "vendor/bin"
)

# Tokens that must never appear inside a release zip.
FORBIDDEN_IN_ZIP=(
    "node_modules/"
    "/src/"
    "/docs/"
    "/bin/"
    "/dist/"
    "/.git/"
    "/.github/"
    "package.json"
    "package-lock.json"
    "composer.json"
    "composer.lock"
    "webpack.config.js"
    "phpcs.xml"
    "tsconfig.json"
    ".DS_Store"
    ".map"
    ".scss"
)

# Set to 1 by release.sh --dry-run so file edits become no-ops.
DRY_RUN="${DRY_RUN:-0}"

log() { printf '\033[1m==>\033[0m %s\n' "$*"; }
step() { printf '\033[36m  •\033[0m %s\n' "$*"; }
warn() { printf '\033[33mwarning:\033[0m %s\n' "$*" >&2; }
die() { printf '\033[31merror:\033[0m %s\n' "$*" >&2; exit 1; }

# Run a command unless we are previewing a dry run.
run() {
    if [ "$DRY_RUN" -eq 1 ]; then
        printf '  [dry-run] %s\n' "$*"
        return 0
    fi
    "$@"
}

# sed in place on GNU sed and BSD/macOS sed alike.
sed_inplace() {
    local file="$1"
    shift
    sed -i.bak -E "$@" "$file"
    rm -f "$file.bak"
}

# Current version from the plugin header.
version_get() {
    grep -m1 -E '^[[:space:]]*\*[[:space:]]*Version:' "$PLUGIN_DIR/$MAIN_FILE" \
        | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' || true
}

# Current version from the runtime constant, used for update checks.
version_get_constant() {
    grep -m1 -E "^[[:space:]]*const[[:space:]]+$VERSION_CONST[[:space:]]*=" "$PLUGIN_DIR/$CLASS_VERSION_FILE" \
        | grep -oE "[0-9]+\.[0-9]+\.[0-9]+" || true
}

version_bump() {
    local current="$1" part="$2"
    local major minor patch
    major="$(printf '%s' "$current" | cut -d. -f1)"
    minor="$(printf '%s' "$current" | cut -d. -f2)"
    patch="$(printf '%s' "$current" | cut -d. -f3)"
    case "$part" in
        # 1.0.4 -> 1.0.5
        patch) echo "$major.$minor.$((patch + 1))" ;;
        # 1.0.4 -> 1.1.0
        minor) echo "$major.$((minor + 1)).0" ;;
        # 1.0.4 -> 2.0.0
        major) echo "$((major + 1)).0.0" ;;
        *) die "Unknown version part '$part'. Use patch (default), minor or major." ;;
    esac
}

# Write this plugin's version to every place it is written. Header, runtime
# constant, wp.org readme and package.json drift apart silently otherwise, and
# each drift shows up differently in the field (wrong update prompt, wrong
# "new version" banner, wrong plugin header in the installer).
#
# This is the only thing in the repository that writes a version: build.sh must
# stay read-only, so a zip can never claim a version nobody released.
version_sync() {
    local new="$1"

    if [ "$DRY_RUN" -eq 1 ]; then
        log "dry-run: set version $new in $MAIN_FILE, $README_FILE, $PACKAGE_FILE"
        return 0
    fi

    [ -n "$new" ] || die "version_sync called without a version"

    if [ -f "$PLUGIN_DIR/$MAIN_FILE" ]; then
        sed_inplace "$PLUGIN_DIR/$MAIN_FILE" \
            "s|^([[:space:]]*\\*[[:space:]]*Version:[[:space:]]*)[0-9]+\\.[0-9]+\\.[0-9]+.*$|\\1$new|"
    fi

    if [ -f "$PLUGIN_DIR/$CLASS_VERSION_FILE" ]; then
        sed_inplace "$PLUGIN_DIR/$CLASS_VERSION_FILE" \
            "s|(const[[:space:]]+$VERSION_CONST[[:space:]]*=[[:space:]]*')[^']+(')|\\1$new\\2|"
    fi

    if [ -f "$PLUGIN_DIR/$README_FILE" ]; then
        sed_inplace "$PLUGIN_DIR/$README_FILE" \
            "s|^(Stable tag:[[:space:]]*).*$|\\1$new|"
    fi

    if [ -f "$PLUGIN_DIR/$PACKAGE_FILE" ]; then
        sed_inplace "$PLUGIN_DIR/$PACKAGE_FILE" \
            "s|(\"version\"[[:space:]]*:[[:space:]]*\")[^\"]+(\")|\\1$new\\2|"
    fi
}

# The Pro add-on sits next to this plugin and refuses to boot below a minimum
# free version. That floor is deliberately *not* the same thing as the version
# being released, so it is raised only when the release asks for it.
pro_plugin_dir() {
    local dir="${SSC_PRO_PLUGIN_DIR:-$PLUGIN_DIR/../staysuite-companion-pro}"
    [ -d "$dir" ] || return 1
    printf '%s' "$dir"
}

version_min_free_get() {
    local dir
    dir="$(pro_plugin_dir)" || return 0
    grep -m1 -E '^[[:space:]]*const[[:space:]]+MIN_FREE_VERSION' "$dir/includes/Core/Pro.php" \
        | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' || true
}

# Rewrite every place that states which free version Pro requires: the gate
# constant, both readmes and the architecture doc. The Pro outdated-free
# notice renders MIN_FREE_VERSION dynamically, so it needs no rewrite. The
# patterns match any existing version rather than one specific number, so this
# stays correct however many releases have gone by.
version_sync_min_free() {
    local new="$1" dir pro_class
    [ -n "$new" ] || return 0

    if ! dir="$(pro_plugin_dir)"; then
        warn "Pro plugin not found next to $PLUGIN_DIR — skipped the minimum-free-version sync"
        return 0
    fi
    pro_class="$dir/includes/Core/Pro.php"

    if [ "$DRY_RUN" -eq 1 ]; then
        log "dry-run: set the Pro minimum free version to $new ($pro_class, its README/readme.txt, docs/pro.md)"
        return 0
    fi

    sed_inplace "$pro_class" \
        "s|(const MIN_FREE_VERSION = ')[^']+(')|\\1$new\\2|"

    if [ -f "$dir/readme.txt" ]; then
        sed_inplace "$dir/readme.txt" \
            "s|(Needs StaySuite Companion )[0-9]+\\.[0-9]+\\.[0-9]+(\\+ active)|\\1$new\\2|"
    fi

    if [ -f "$dir/README.md" ]; then
        sed_inplace "$dir/README.md" \
            "s|(free plugin, )[0-9]+\\.[0-9]+\\.[0-9]+(\\+)|\\1$new\\2|"
    fi

    if [ -f "$PLUGIN_DIR/docs/pro.md" ]; then
        sed_inplace "$PLUGIN_DIR/docs/pro.md" \
            "s|(is \`>= )[0-9]+\\.[0-9]+\\.[0-9]+(\`)|\\1$new\\2|"
    fi
}

# Remind the user that the sibling Pro repo now carries edits. It is a separate
# git repository, so those files cannot be committed from this release.
pro_changes_note() {
    local dir changed
    dir="$(pro_plugin_dir)" || return 0
    command -v git >/dev/null 2>&1 || return 0
    git -C "$dir" rev-parse --git-dir >/dev/null 2>&1 || return 0

    changed="$(git -C "$dir" status --porcelain)"
    [ -n "$changed" ] || return 0

    warn "The Pro plugin has uncommitted changes from this release - release it separately:"
    printf '%s\n' "$changed" | sed 's/^/    /' >&2
}

# Fail loudly when the version in the header, the runtime constant, the
# wp.org readme and package.json disagree. Any mismatch makes a shipped zip
# lie about itself. package.json belongs in this check because it drifts
# silently: it sat a whole release behind the header unnoticed.
version_verify() {
    local header constant stable packaged
    header="$(version_get)"
    constant="$(version_get_constant)"
    stable="$(grep -m1 -E '^Stable tag:' "$PLUGIN_DIR/$README_FILE" | awk '{print $3}' | tr -d '\r')"
    packaged="$(grep -m1 -E '"version"' "$PLUGIN_DIR/$PACKAGE_FILE" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' || true)"

    [ -n "$header" ] || die "No Version header in $MAIN_FILE"
    [ -n "$constant" ] || die "No const $VERSION_CONST in $CLASS_VERSION_FILE"
    [ -n "$stable" ] || die "No 'Stable tag:' in $README_FILE"
    [ -n "$packaged" ] || die "No \"version\" in $PACKAGE_FILE"

    if [ "$header" != "$constant" ] || [ "$header" != "$stable" ] || [ "$header" != "$packaged" ]; then
        die "Version mismatch: header=$header constant=$constant readme=$stable package=$packaged. Fix with: bin/release.sh --sync-only"
    fi
}

# Insert a Keep a Changelog entry above the newest entry in readme.txt.
# wp.org renders this section on the plugin page, so it is the public changelog.
changelog_insert() {
    local version="$1" notes_file="$2"

    [ -f "$notes_file" ] || die "Changelog notes not found: $notes_file"

    if [ "$DRY_RUN" -eq 1 ]; then
        log "dry-run: insert '= $version =' entry into $README_FILE from $(basename "$notes_file")"
        return 0
    fi

    # Normalise to bullet lines so the section stays uniform.
    local notes entry tmp
    notes="$(grep -vE '^[[:space:]]*$' "$notes_file")"
    [ -n "$notes" ] || die "Changelog notes are empty: $notes_file"

    entry="= $version ="
    tmp="$(mktemp)"
    printf '%s\n' "$entry" > "$tmp"
    # No trailing blank line: the readme already has one before the next entry.
    printf '%s\n' "$notes" | sed -E 's|^[[:space:]]*[-*+]?[[:space:]]*|* |' >> "$tmp"

    # Splice in directly after the "== Changelog ==" heading.
    awk -v block="$tmp" '
        /^== Changelog ==$/ && !done { print; while ((getline line < block) > 0) print line; done = 1; next }
        { print }
    ' "$PLUGIN_DIR/$README_FILE" > "$PLUGIN_DIR/$README_FILE.new"
    mv "$PLUGIN_DIR/$README_FILE.new" "$PLUGIN_DIR/$README_FILE"
    rm -f "$tmp"
}

# Remove editor cruft and dev leftovers from the staged tree.
stage_prune() {
    local root="$1"
    find "$root" -name '.DS_Store' -delete 2>/dev/null || true
    find "$root" -name '*.map' -delete 2>/dev/null || true
    find "$root" -name '*.log' -delete 2>/dev/null || true
    find "$root" -name '*~' -delete 2>/dev/null || true
    find "$root" -name '.git' -type d -prune -exec rm -rf {} + 2>/dev/null || true
    find "$root" -name 'node_modules' -type d -prune -exec rm -rf {} + 2>/dev/null || true
}

# Produce vendor/ in the staging tree.
#
# fresh: composer install --no-dev inside the stage, so dev tools (phpcs and
#        friends) never enter the zip. Run in the stage rather than the working
#        copy so `composer lint` keeps working locally afterwards.
# copy:  no composer available — reuse the working vendor minus known dev paths.
stage_vendor() {
    local root="$1" mode="$2"

    case "$mode" in
        fresh)
            command -v composer >/dev/null 2>&1 \
                || die "composer not found. Install it or run with --vendor=copy."
            cp "$PLUGIN_DIR/composer.json" "$root/composer.json"
            if [ -f "$PLUGIN_DIR/composer.lock" ]; then
                cp "$PLUGIN_DIR/composer.lock" "$root/composer.lock"
            fi
            log "Installing production dependencies (composer --no-dev)"
            (cd "$root" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)
            rm -f "$root/composer.json" "$root/composer.lock"
            ;;
        copy)
            [ -d "$PLUGIN_DIR/vendor" ] || die "vendor/ not found in $PLUGIN_DIR"
            warn "composer unavailable — copying working vendor/ and pruning known dev packages"
            cp -R "$PLUGIN_DIR/vendor" "$root/vendor"
            local path
            for path in "${DEV_VENDOR_PATHS[@]}"; do
                rm -rf "${root:?}/${path}"
            done
            ;;
        skip)
            die "Refusing to build: vendor/autoload.php is required at runtime. Run without --skip-composer."
            ;;
        *)
            die "Unknown vendor mode: $mode"
            ;;
    esac
}

# Compiled stylesheets, relative to the plugin root. Built from src/scss by
# `npm run build:css`; nothing hand-written ships, so these must be present and
# no older than their Sass sources.
CSS_OUTPUTS=(
    "assets/build/css/ssc-hotel.css"
    "assets/build/css/ssc-admin.css"
)

css_verify() {
    local output compiled stale
    for output in "${CSS_OUTPUTS[@]}"; do
        compiled="$PLUGIN_DIR/$output"
        if [ ! -f "$compiled" ]; then
            die "Missing compiled stylesheet $output. Run: npm run build:css"
        fi
        stale="$(find "$PLUGIN_DIR/src/scss" -name '*.scss' -newer "$compiled" -print -quit 2>/dev/null || true)"
        if [ -n "$stale" ]; then
            die "Compiled $output is older than ${stale#"$PLUGIN_DIR"/}. Run: npm run build:css"
        fi
    done
}

# Assemble the staging tree and zip it into dist/.
stage_and_zip() {
    local version="$1" vendor_mode="$2"
    local stage root out item listed

    stage="$(mktemp -d)"
    root="$stage/$SLUG"
    mkdir -p "$root"

    for item in "${PRODUCTION_PATHS[@]}"; do
        if [ -e "$PLUGIN_DIR/$item" ]; then
            # Keep the in-repo path: cp -R into an existing directory would
            # flatten assets/build to build/ and break every plugin URL.
            mkdir -p "$root/$(dirname "$item")"
            cp -R "$PLUGIN_DIR/$item" "$root/$item"
        else
            step "not present, skipping: $item"
        fi
    done

    stage_prune "$root"
    stage_vendor "$root" "$vendor_mode"

    mkdir -p "$PLUGIN_DIR/dist"
    out="$PLUGIN_DIR/dist/$SLUG-$version.zip"
    rm -f "$out"
    (cd "$stage" && zip -qr "$out" "$SLUG")
    rm -rf "$stage"

    listed="$(unzip -Z1 "$out")"

    # Guard against a silent regression where a dev path creeps back in.
    local forbidden hit
    for forbidden in "${FORBIDDEN_IN_ZIP[@]}"; do
        hit="$(printf '%s\n' "$listed" | grep -F "$forbidden" | head -n 1 || true)"
        if [ -n "$hit" ]; then
            rm -f "$out"
            die "Release zip contained a development file ($hit). Remove it from PRODUCTION_PATHS or prune it."
        fi
    done

    printf '%s\n' "$listed" | grep -q "$SLUG/$MAIN_FILE" \
        || die "Release zip is missing $MAIN_FILE"

    # No stylesheet in the zip means no styles on the site, and the PHP that
    # enqueues it fails silently, so make it a build error rather than a
    # support ticket.
    local css
    for css in "${CSS_OUTPUTS[@]}"; do
        printf '%s\n' "$listed" | grep -q "$SLUG/$css" \
            || die "Release zip is missing $css — run npm run build:css"
    done

    log "Built $(basename "$out") — $(printf '%s\n' "$listed" | wc -l | tr -d ' ') files, $(du -h "$out" | cut -f1 | tr -d ' ')"
    step "$PLUGIN_DIR/dist/$SLUG-$version.zip"
}