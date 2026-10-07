=== StaySuite Companion for WpRentals ===
Contributors: tanmjay
Tags: wprentals, hotel, booking, group booking, blocks
Requires at least: 6.0
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Hotel pages, homepage booking blocks and group quote requests for the WpRentals theme. No theme files are modified.

== Description ==

StaySuite Companion turns the WpRentals theme into a hotel-ready booking site. It requires the WpRentals theme (sold separately on ThemeForest) and will not activate without it.

* **Hotels**: a Hotel post type linked to your existing listings (rooms). Each hotel gets a gallery cover, auto-aggregated facilities, a date search strip, per-room availability badges, room cards using the theme's own sliders, and a map.
* **Homepage blocks**: Hero Search (the theme's own search bar on a full-bleed cover), Term Tablets, Listing Carousels, Payment Strip and Group Booking. Also available as shortcodes.
* **Individual / Group switch**: a capsule toggle above the homepage search. Group mode collects party details and sends a combined quote request, stored as Group Requests in wp-admin and emailed to the site admin, with a confirmation email to the visitor.
* **Theme-native behavior**: search, datepickers, guest panels, sliders, maps and booking all reuse the theme's own components. Submit buttons and icons follow your theme customizer colors automatically.

Built for the [WpRentals](https://themeforest.net/item/wprentals-booking-accommodation-wordpress-theme/12332978) theme. This plugin is independent and is not affiliated with or endorsed by the theme's authors.

Documentation: https://jktanmay.com/products/staysuite-companion/docs
Source code and issue tracker: https://github.com/staysuite-companion/staysuite-companion

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher (PHP 8.3 or newer recommended)
* The WpRentals theme, installed and active

== Installation ==

1. Make sure the WpRentals theme is installed and active.
2. Upload the `staysuite-companion` folder to `/wp-content/plugins/`, or install it from Plugins → Add New.
3. Activate the plugin through the Plugins screen.
4. Create Hotels under the new Hotels menu and assign rooms to them (room edit screen → Hotel box, Quick Edit, or Hotels → Assign Rooms).
5. Open the **Homepage - StaySuite** page created on activation, edit its blocks, and set it as the static homepage under Settings → Reading. Or build your own page and pick the **StaySuite Homepage** template in Page Attributes.

== Frequently Asked Questions ==

= Do I need WpRentals? =
Yes. The plugin reuses the theme's search, booking, map and slider components and refuses to activate without it.

= Will it survive theme updates? =
Yes. Nothing in the theme is modified; all overrides live in the plugin (templates, styles, scripts).

= Where do group quote requests go? =
Each request is stored as a Group Request post and emailed to the admin address. Manage them under Group Requests in wp-admin.

= How is room availability checked? =
Against the theme's own booking engine (`wpestate_check_booking_valability`), per room and date range. Booking itself stays on the room page.

= Can I use the blocks without the Hotels feature? =
Yes. The homepage blocks and shortcodes work on any page, independent of whether you have created Hotels.

== Screenshots ==

1. Homepage hero with Individual/Group capsule and pill search bar.
2. Hotel page: gallery cover, facilities, room cards and map.

== External Services ==

Hotel pages display a map using the Leaflet library that ships with the WpRentals theme (it is not bundled in this plugin).

When a visitor views a hotel page that has map coordinates, the visitor's browser requests map tiles from OpenStreetMap's tile servers (tile.openstreetmap.org). OpenStreetMap receives the visitor's IP address and the map area requested. Terms of use: https://osmfoundation.org/wiki/Terms_of_Use. Privacy policy: https://osmfoundation.org/wiki/Privacy_Policy.

== Privacy ==

Group quote requests are stored in your WordPress database as Group Request posts and sent by email to the site admin. They contain the details the visitor enters in the form (for example name, email and party information). You are responsible for handling this data in line with your local privacy laws. The plugin sends no data to the plugin author.

== Changelog ==

= 1.0.0 =
* Initial release.
