# Settings (StaySuite → tabbed page)

One admin page (top-level **StaySuite** menu, below **Hotels**) with tabs — General/Settings plus Go Pro in free; License and AI Settings tabs inject from Pro via the `ssc.admin.tabs` JS filter. REST-backed (`ssc/v1/settings`), no page reloads.

| Setting | Effect |
|---|---|
| Default Guest Number (2) | Preselected in every Guests panel via the theme steppers; 0 disables |
| Search colors (theme) | Follow customizer, or custom submit + hover hexes |
| Search results return (Hotels) | Homepage `?s=` and advanced search return `ssc_hotel` posts with hotel cards; Listings restores theme behavior |
| Multi-room selection (on, Group quotes) | “Add to quote” buttons on hotel room cards so visitors can request several rooms in one group request (Pro renders them); off leaves instant booking only |
| Required contact (Email only, Group quotes) | Which contact the group quote form requires: email only, phone only, or both; name is always required. Phone is never format-checked |
| Signup phone (Required, Accounts) | Phone field on every theme signup form (modal, shortcode, mobile, booking): off hides it, optional shows it, required rejects the signup server-side. Stored in the theme's `mobile` field |
| Signup gender (Profile only, Accounts) | Male / Female / Other. Off hides it everywhere; profile-only keeps it editable on the dashboard profile; signup-optional shows it skippable on signup; required forces it on signup and rejects empty signups. Stored privately in `_ssc_gender` |
| Send confirmation receipt automatically (on, Documents) | Agoda-style receipt to the guest when a theme booking or group request turns confirmed. Preview and manual resend ship in Pro |
| Receipt notes (Documents) | Check-in notes printed on every receipt (photo ID, extra charges, no-show policy) |
| Delete all StaySuite data on uninstall (off, Danger Zone) | `uninstall.php` wipes Hotels, Group Requests, `_ssc_*` meta and options; off keeps content |

Menu map: **Hotels** (generic building icon, right after Listings) holds All Hotels, Add New, Assign Rooms (paged, searchable, per-row + bulk assign). **StaySuite** (brand logo) holds the tab page (Settings + Go Pro, License + AI Settings tabs injected by Pro), Group Requests, and — only when Pro is absent — Go Pro.
