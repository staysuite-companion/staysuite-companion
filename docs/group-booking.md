# Group booking

## Individual / Group capsule

On the StaySuite Homepage template only, a capsule toggle mounts above the homepage search bar. Other pages keep the native search untouched.

* **Individual** — the theme search behaves normally.
* **Group** — the search submit routes into the quote flow; a quote form portals below the search (overlapping the cover edge, cover height fixed) with a smooth expand animation, and the page scrolls the capsule just below the header.

Next to a theme search bar the form reads location/dates/guests live from it; standalone (block/shortcode) it renders its own Where/dates/guests fields.

## Quote flow

1. Extras collected: rooms, male/female split, budget range, name/email/phone, requirements.
2. `ssc_group_quote` AJAX → honeypot check → strict validation (name, email, Y-m-d dates with check-out after check-in and no past check-in, caps on lengths and party sizes) → per-IP rate limit (5 per 10 minutes, see `ssc_quote_rate_limit`) → server matching (city, capacity, budget) → suggested stays returned inline.
3. Every request is stored as an `ssc_group_request` post and emailed to the site admin, with a confirmation email to the visitor. The admin mail carries a `Reply-To` with the visitor's address.

Stale nonces (cached pages) return an `ssc_nonce_expired` code; the form fetches a fresh nonce from `ssc_quote_nonce` and retries once.

Manage requests under Group Requests in wp-admin (status meta box included). Requests hold personal data, so the post type is administrator-only (`manage_options` on every capability).

## Multi-room selection (Pro group quotes)

On hotel pages, each room card carries **Book now** (instant theme booking, this room only) next to **Add to quote** (adds the room to a combined request — no payment). The visitor journey:

1. Add rooms with quantities (steppers; the same room can be added multiple times). A tray shows rooms · nights · live total; with no usable dates it says so instead of guessing.
2. **Review** opens a stepped modal: rooms with quantities and per-room totals, editable dates/guests, live availability re-check (unavailable rooms must be removed or dates changed), then contact details with a no-payment note.
3. **Send request** stores one `ssc_group_request` with the room IDs, per-room quantities (`_ssc_pro_room_qty`) and the total estimate; the confirmation screen lists the reference and the three next steps (confirm availability → quote by email → pay the quote link).

Dates travel as `check_in`/`check_out` plus the theme booking params; Y-m-d and theme-format dates are both accepted and normalized server-side. Availability always runs through the theme engine in 4-digit display form (`Repository::to_engine_date()`), which also fixed dated quote matching on sites whose date format is not Y-m-d.

The request screen shows a **Selected rooms** row (`2 × Title`) with the **Estimated total**, a **Quote** list column, and the admin email digest carries the same selection line. Disable the whole flow in Settings → Group quotes → Multi-room selection (or the `ssc_group_selection_enabled` filter).

## Related

* `[ssc_group_booking title="…"]` / `ssc/group-booking` block for standalone placement.
* Guest panels default to 2 adults everywhere via the theme's own steppers.
