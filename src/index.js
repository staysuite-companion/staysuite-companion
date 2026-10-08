/**
 * Companion frontend entry.
 *
 * Mounts a HotelBadge React root inside every WpRentals listing card.
 * Cards expose their room ID via .listing_wrapper[data-listid].
 */
import { createRoot } from 'react-dom/client';
import HotelBadge from './cards/HotelBadge';
import CarouselNav from './carousels/Carousel';
import BookingForm from './booking/BookingForm';
import SearchMode from './search/SearchMode';

document.addEventListener('DOMContentLoaded', () => {
    mountHotelBadges();
    mountCarouselNavs();
    mountBookingForms();
    mountSearchModes();
    pruneHiddenHeaderSearch();
    mountHeroCalendars();
    mountHotelMaps();
    window.addEventListener('load', mountHeroCalendars);
    window.addEventListener('load', defaultGuestCounts);
});

/**
 * Mount a HotelBadge root inside every listing card.
 *
 * @return {void}
 */
function mountHotelBadges() {
    if (typeof sscCards === 'undefined') {
        return;
    }

    document
        .querySelectorAll('.listing_wrapper[data-listid]')
        .forEach((node) => {
            if (node.querySelector('[data-ssc-badge]')) {
                return;
            }

            const roomId = parseInt(node.getAttribute('data-listid'), 10);
            if (!roomId) {
                return;
            }

            const mount = document.createElement('span');
            mount.setAttribute('data-ssc-badge', '1');

            const target = node.querySelector('.category_name') || node;
            target.appendChild(mount);

            createRoot(mount).render(<HotelBadge roomId={roomId} />);
        });
}

/**
 * Attach prev/next controls to every server-rendered carousel.
 *
 * @return {void}
 */
function mountCarouselNavs() {
    document.querySelectorAll('[data-ssc-carousel]').forEach((root) => {
        if (root.querySelector('[data-ssc-carousel-nav]')) {
            return;
        }
        const track = root.querySelector('.ssc-carousel-track');
        if (!track) {
            return;
        }
        const mount = document.createElement('div');
        mount.setAttribute('data-ssc-carousel-nav', '1');
        root.appendChild(mount);
        createRoot(mount).render(<CarouselNav track={track} />);
    });
}

/**
 * Mount the group booking form wherever requested.
 *
 * @return {void}
 */
function mountBookingForms() {
    if (typeof sscBooking === 'undefined') {
        return;
    }
    document.querySelectorAll('[data-ssc-group-booking]').forEach((node) => {
        if (node.querySelector('[data-ssc-booking-root]')) {
            return;
        }
        const mount = document.createElement('div');
        mount.setAttribute('data-ssc-booking-root', '1');
        node.appendChild(mount);
        createRoot(mount).render(<BookingForm title={node.getAttribute('data-title') || ''} />);
    });
}

/**
 * Remove the suppressed theme header search on hero and hotel pages.
 *
 * It is already display:none, but its duplicate check_in/check_out IDs
 * steal the theme's datepicker binding from the visible forms.
 *
 * @return {void}
 */
function pruneHiddenHeaderSearch() {
    if (!document.body.classList.contains('ssc-has-hero')
        && !document.body.classList.contains('single-ssc_hotel')) {
        return;
    }
    document.querySelectorAll('#search_wrapper').forEach((node) => node.remove());
}

/**
 * Bind the theme daterangepicker to hero widget date fields.
 *
 * Hero inputs get unique IDs (duplicates would bind the hidden header
 * copy instead), then the theme's own binder wires them up.
 *
 * @return {void}
 */
function mountHeroCalendars() {
    if (typeof wpestaste_check_in_out_enable !== 'function') {
        return;
    }
    document.querySelectorAll('.ssc-hero .search_wr_elementor').forEach((root, index) => {
        const inEl = root.querySelector('#check_in');
        const outEl = root.querySelector('#check_out');
        if (!inEl || !outEl) {
            return;
        }
        const inId = `ssc_check_in_${index}`;
        const outId = `ssc_check_out_${index}`;
        if (inEl.id !== inId) {
            inEl.id = inId;
        }
        if (outEl.id !== outId) {
            outEl.id = outId;
        }
        if (!inEl.dataset.sscBound) {
            inEl.dataset.sscBound = '1';
            wpestaste_check_in_out_enable(inId, outId);
        }
    });
}
/**
 * Render hotel single maps with Leaflet (theme ships Leaflet globally).
 *
 * The theme's own listing-map script only runs for estate_property
 * singles, so hotels get a first-party map from the same library.
 *
 * @return {void}
 */
function mountHotelMaps() {
    if (typeof L === 'undefined') {
        return;
    }
    document.querySelectorAll('.ssc-hotel-map').forEach((node) => {
        if (node.dataset.sscMapped) {
            return;
        }
        const lat = parseFloat(node.dataset.lat);
        const lng = parseFloat(node.dataset.lng);
        if (!isFinite(lat) || !isFinite(lng) || (lat === 0 && lng === 0)) {
            return;
        }
        node.dataset.sscMapped = '1';
        const map = L.map(node, { scrollWheelZoom: false }).setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
        L.marker([lat, lng])
            .addTo(map)
            .bindPopup(node.dataset.title || '');
        setTimeout(() => map.invalidateSize(), 400);
    });
}
/**
 * Default every pristine theme guest panel to 2 adults.
 *
 * Clicks the theme's own Adults "+" twice so its totals, labels and
 * hidden guest_no stay consistent (max-guest caps still apply).
 * Only panels still sitting at 0 are touched; runs after the theme
 * binds its controls on window load.
 *
 * @return {void}
 */
function defaultGuestCounts() {
    var adults = 2;
    if (typeof sscSettings !== 'undefined' && typeof sscSettings.adults !== 'undefined') {
        adults = parseInt(sscSettings.adults, 10);
    }
    if (!(adults > 0)) {
        return;
    }
    setTimeout(() => {
        document.querySelectorAll('.wpestate_guest_no_buttons').forEach((panel) => {
            if (panel.dataset.sscDefaulted) {
                return;
            }
            const value = panel.querySelector('.steper_value_adults');
            const plus = panel.querySelector('.adults_control_plus');
            if (!value || !plus || value.textContent.trim() !== '0') {
                return;
            }
            panel.dataset.sscDefaulted = '1';
            for (let i = 0; i < adults; i++) {
                plus.click();
            }
        });
    }, 300);
}
/**
 * Mount an Individual/Group switch above the homepage theme search bar.
 *
 * Homepage only (body.ssc-homepage): everywhere else the theme search
 * stays exactly as is. Works with the Elementor widget and theme
 * templates alike because it keys off the shared search markup.
 * Visibility is per-cover: a hero block with show_capsule off carries
 * data-ssc-capsule="0" and its search bar gets no capsule.
 *
 * @return {void}
 */
function mountSearchModes() {
    if (typeof sscBooking === 'undefined') {
        return;
    }
    if (!document.body.classList.contains('ssc-homepage')) {
        return;
    }
    document.querySelectorAll('.advanced_search_form_wrapper, .search_wr_elementor').forEach((wrapper) => {
        if (wrapper.previousElementSibling && wrapper.previousElementSibling.hasAttribute('data-ssc-mode')) {
            return;
        }
        const hero = wrapper.closest('.ssc-hero');
        if (hero && hero.getAttribute('data-ssc-capsule') === '0') {
            return;
        }
        // The theme header search is suppressed on hero pages (see
        // body.ssc-has-hero styles); only enhance the visible one.
        if (document.body.classList.contains('ssc-has-hero') && wrapper.closest('#search_wrapper')) {
            return;
        }
        const mount = document.createElement('div');
        mount.setAttribute('data-ssc-mode', '1');
        wrapper.parentNode.insertBefore(mount, wrapper);
        createRoot(mount).render(<SearchMode wrapper={wrapper} />);
    });
}
