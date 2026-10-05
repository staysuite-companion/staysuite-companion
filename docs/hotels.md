# Hotels

## Post type

`ssc_hotel` (Hotels menu, slug `/hotels/`). Fields: title, featured image, content, plus meta:

| Meta key | Meaning |
|---|---|
| `_ssc_city` | `property_city` term slug |
| `_ssc_address` | Free-text address |
| `_ssc_phone` | Contact phone |
| `_ssc_featured` | `1` = featured hotel |
| `property_latitude` / `property_longitude` | Map coords (auto-inherited, see below) |
| `property_price` | Lowest room price (auto-synced, feeds theme cards + map pins) |

## Assigning rooms

A room belongs to a hotel via `_ssc_hotel_id` on the listing. Three UIs:

* Room edit screen → **Hotel (StaySuite)** box (side panel, right after Publish) with an **Original price** field.
* Rooms list → **Quick Edit** → Hotel dropdown (preselected).
* Hotels → **Assign Rooms** for bulk assignment, with assigned/unassigned filters.

## Original (was) price

`_ssc_original_price` per room. Display-only: when higher than the theme's `property_price`, hotel room cards show it struck through above the booking price. Booking and invoices always use the theme price.

## Hotel display sync

`Repository::sync_hotel_data()` runs on every room/hotel save and backfills the fields theme templates read directly: `property_price` (lowest room price, so cards and map pins show `from X/night`), coords via `ensure_coords()`, the rooms' union of `property_city/area/category/action/status` terms (so city lines and verified badges render), and `_ssc_city` from the first city term when unset.

## Hotel single page

Covered in [Hotel single page](hotel-single.md): gallery, aggregated facilities, date strip, availability, glass room cards with theme sliders, map.
