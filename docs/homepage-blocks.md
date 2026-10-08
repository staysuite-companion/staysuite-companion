# Homepage blocks & shortcodes

Five dynamic (server-rendered) blocks with live editor previews via `ssc/v1/preview`. Every block has an equivalent shortcode.

## `ssc/hero-search` / `[ssc_hero]`

Full-bleed cover (page featured image or `image_id`) with title, subtitle and the theme's own search widget (location, dates, guests, circular submit).

Attributes: `title`, `subtitle`, `image_id`, `search_mode` (`theme`|`simple`|`none`), `show_search` (`1`|`0`), `hero_height` (cover height in vh, 30–100, default 75), `show_capsule` (`1`|`0`, Individual/Group pill above the search), `animate_form` (`1`|`0`, group form pop-out animation), plus core `align` (use `full`).

Leave `image_id` empty to use the page's featured image. That fallback exists because attachment IDs do not survive copying content between databases: a stored `image_id` that worked locally can point at an unrelated (or missing) attachment on staging or production, leaving a cover with only its background colour.

```
[ssc_hero title="Find your next stay" subtitle="Hotels across Bangladesh" image_id="123"]
```

## `ssc/term-tablets` / `[ssc_term_tablets]`

Glass gradient pills linking term archives, with stay counts. Terms with a **Featured photo** (City/Category/Type/Area edit screens, added by this plugin) render as photo cards with a legibility gradient; the rest fall back to sea-glass gradients.

Attributes: `taxonomy` (`property_city`|`property_category`|`property_action_category`|`property_area`), `number` (default 6), `hide_empty` (`1`|`0`), `show_divider` (`1`|`0`, hairline below the section).

```
[ssc_term_tablets taxonomy="property_action_category" number="6" hide_empty="1"]
```

## `ssc/listing-carousel` / `[ssc_listing_carousel]`

Horizontal snap carousel of theme listing cards with gallery-style flanking arrows.

Attributes: `title`, `source` (`rooms`|`hotels`), `taxonomy`, `term`, `city`, `count` (default 8), `featured_only` (`1`|`0`), `include_ids`, `order` (`featured`|…), `show_divider` (`1`|`0`, hairline below the section).

The `taxonomy` + `term` filter works for both rooms and hotels (hotels inherit city/area/category terms from their linked rooms), so area-wise or city-wise hotel rows work. The editor offers term and hand-picked post dropdowns via `ssc/v1/carousel-options`; the `city` attribute remains for shortcodes filtering hotels by city meta. A hand-picked `include_ids` post overrides every other filter and ordering.

```
[ssc_listing_carousel title="Popular stays" source="rooms" count="8"]
```

## `ssc/payment-strip` / `[ssc_payment_strip]`

Label + banner image (payment logos).

Attributes: `title` (default `Pay With`), `image_id`.

## `ssc/group-booking` / `[ssc_group_booking]`

Standalone group quote form (see [Group booking](group-booking.md)). Attribute: `title`.
