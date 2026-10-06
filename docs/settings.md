# Settings (StaySuite → tabbed page)

One admin page (top-level **StaySuite** menu, below **Hotels**) with tabs — General/Settings plus Go Pro in free; License and AI Settings tabs inject from Pro via the `ssc.admin.tabs` JS filter. REST-backed (`ssc/v1/settings`), no page reloads.

| Setting | Effect |
|---|---|
| Individual / Group capsule (on) | Capsule above the homepage search; off = native search everywhere |
| Default adults (2) | Preselected in every Guests panel via the theme steppers; 0 disables |
| Section dividers (on) | Hairlines between homepage sections (`ssc-no-dividers` opt-out class) |
| Animations (on) | Group form pop-out (`ssc-no-animations` opt-out class) |
| Hero cover height (75 vh) | Emitted as `--ssc-hero-h`, clamped 30–100 |
| Search colors (theme) | Follow customizer, or custom submit + hover hexes |
| Search results return (Hotels) | Homepage `?s=` and advanced search return `ssc_hotel` posts with hotel cards; Listings restores theme behavior |
| Multi-room selection (on, Group quotes) | “Add to quote” buttons on hotel room cards so visitors can request several rooms in one group request (Pro renders them); off leaves instant booking only |
| Required contact (Email only, Group quotes) | Which contact the group quote form requires: email only, phone only, or both; name is always required. Phone is never format-checked |
| Delete all StaySuite data on uninstall (off, Danger Zone) | `uninstall.php` wipes Hotels, Group Requests, `_ssc_*` meta and options; off keeps content |

Menu map: **Hotels** (generic building icon, right after Listings) holds All Hotels, Add New, Assign Rooms (paged, searchable, per-row + bulk assign). **StaySuite** (brand logo) holds the tab page (Settings + Go Pro, License + AI Settings tabs injected by Pro), Group Requests, and — only when Pro is absent — Go Pro.
