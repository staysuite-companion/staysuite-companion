# Hooks & data

## Actions / filters

| Hook | Type | Notes |
|---|---|---|
| `ssc_loaded` | action | Fires after the plugin container is set up |
| `ssc_group_request_saved` (`$request_id`, `$input`, `$matches`) | action | After a group request is stored + mailed |
| `ssc_quote_payload` (`$input`, `$raw`) | filter | Extend sanitized quote fields |
| `ssc_quote_rate_limit` (`array('max'=>5,'window'=>600,'suggest_max'=>30,'suggest_window'=>600)`) | filter | Throttling per hashed IP; quotes and suggestions use separate buckets |
| `ssc_group_selection_enabled` (`$enabled`) | filter | Group-quote room selection availability (Pro Add to quote buttons); setting default |
| `ssc_signup_genders` (`$genders`) | filter | Signup gender options (slug => label); validated against slugs on save |
| `ssc_signup_phone_error` / `ssc_signup_gender_error` (`$message`) | filter | Required-field rejection text on theme signup forms |
| `ssc_hotel_search_settings` / `ssc_hero_search_settings` (`$settings`) | filter | Widget field composition |
| `ssc_search_vars` (`$css`, `$submit`) | filter | Override search-bar color mapping |
| `ssc_single_hotel_template` (`$path`) | filter | Override the hotel template file |
| `ssc_search_template` (`$path`) | filter | Override the hotel search template file |
| `ssc_room_card_actions` (`$room_id`) | action | Extra buttons per room card |
| `ssc/v1/preview` | REST (POST, `edit_posts`) | `{block, attributes}` → `{html}` editor previews |
| `ssc/v1/carousel-options` | REST (GET, `edit_posts`) | `?taxonomy=&source=` → `{terms, posts}` carousel editor dropdowns |
| `ssc/v1/settings` | REST (GET/POST, `manage_options`) | Settings read/save |
| `ssc.admin.tabs` | JS filter | Admin tab registry (Pro injects License/AI tabs) |
| `ssc-pro/v1/license`, `/ai-settings`, `/ai-test` | REST (Pro, `manage_options`) | License + AI settings backend |

AJAX: `ssc_group_suggest` (anonymous stay suggestions, nothing stored), `ssc_group_quote` (quote flow), `ssc_quote_nonce` (fresh nonce for cached pages), `ssc_resolve_hotels` (card badges), `ssc_dismiss_notice` (per-user notice dismissal). JS globals: `sscBooking` (`ajaxurl`, `quote_nonce`, `cities`, `date_format`, `contact_required`), `sscSignup` (`phone`, `gender`, `genders` modes + gender options for injected signup fields), `sscCards` (`ajaxurl`, `nonce`), `sscLinks` (canonical outbound URLs), `sscTermImage` (media-picker title), `sscNotice` (`ajaxurl`, `nonce`).

## Stored data

* Post types: `ssc_hotel` (slug `/hotels/`), `ssc_group_request`.
* Room link: `_ssc_hotel_id` on listings; display price `_ssc_original_price`; hotel `_ssc_city/_ssc_address/_ssc_phone/_ssc_featured`; coords reuse theme keys `property_latitude/longitude`.
* Accounts: signup gender in `_ssc_gender` (male/female/other); phone reuses the theme's `mobile` user meta, never duplicated.
* Options: `ssc_settings` (includes `delete_on_uninstall`), `ssc_homepage_page_id` (homepage page created on activation). Short-lived transients: `ssc_rl_*` (quote rate limiting, hashed IPs only). User meta: `ssc_theme_notice_dismissed` (theme-notice dismissal).

## deliberate extension seams (Pro roadmap)

The free plugin is intentionally thin on hooks today. Before building Pro, add: `ssc_room_card_actions` (extra buttons per room card), `ssc_quote_payload` (extend quote fields server-side), `ssc_strip_fields` (hotel strip composition), `ssc_search_vars` (override color mapping). See the freemium plan for the full split.
