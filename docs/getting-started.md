# Getting started

## Requirements

* WordPress 6.0+, PHP 7.4+ (8.3+ recommended)
* **WpRentals theme active** — the plugin refuses to activate without it, since it reuses the theme's search, booking, map and slider components.

## Install

1. Upload the plugin zip (Plugins → Add New → Upload) or ship the folder as `staysuite-companion` into `/wp-content/plugins/`.
2. Activate. On activation the plugin registers its post types, creates a **Homepage - StaySuite** page, and flushes rewrite rules.

## Set up the homepage

Activation creates a page called **Homepage - StaySuite**, already filled with the full homepage layout and already on the **StaySuite Homepage** template. Activating again never creates a second copy: if the site already has that page, or any page already on the template, that page is reused instead.

The plugin does not change Settings → Reading. If the site shows the latest posts, set the created page as the static front page yourself.

1. Open **Homepage - StaySuite** and edit it: `ssc/hero-search` (full-width cover + theme search bar), `ssc/term-tablets`, `ssc/listing-carousel`, `ssc/payment-strip`. See [Homepage blocks](homepage-blocks.md).
2. Settings → Reading → **Your homepage displays: A static page** → select it as the homepage.
3. The transparent theme header is forced on automatically for this template.

To start from a blank page instead, create one and pick the **StaySuite Homepage** template (Page Attributes → Template).

The template is not cosmetic. It provides the full-width layout, the
transparent overlay header and the centred content container for the sections
below the cover — none of which CSS can reproduce on its own. Miss it and the
homepage still renders, just wrong. If the front page is not using it, wp-admin
shows a warning with an **Apply the StaySuite Homepage template** button; do
that instead of hunting for Page Attributes.

## Set up hotels

1. Hotels → Add New: title, featured image, city/address/phone, content.
2. Assign rooms three ways: room edit screen → Property Details → **Hotel (StaySuite)** tab, rooms list → Quick Edit → Hotel, or Hotels → Assign Rooms for bulk.
3. Open the hotel page: gallery cover, facilities, date strip, room cards and map render from the rooms' own data.

No theme file is ever modified — everything ships as plugin templates, styles and scripts.
