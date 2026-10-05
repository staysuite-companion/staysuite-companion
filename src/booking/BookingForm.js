/**
 * Group booking flow in two steps.
 *
 * Step 1 (anonymous): trip details only — location, dates and guests come
 * live from the nearby theme search when there is one (external mode),
 * otherwise from the form's own Where/dates/guests fields. Submitting
 * fetches suggested stays via ssc_group_suggest; nothing is stored.
 *
 * Step 2 (quote): the visitor picks stays of interest and leaves contact
 * details (which field is mandatory follows Settings → Required contact).
 * Submitting sends everything via ssc_group_quote, which stores the
 * request and notifies the admin.
 */
import { useEffect, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Find the active theme search wrapper feeding this form.
 *
 * Matches the classic template root and the Elementor widget root alike;
 * SearchMode flags whichever wrapper it enhances with ssc-group-active.
 *
 * @return {Element|null} Theme search wrapper or null.
 */
function findThemeSearch() {
    return document.querySelector('.advanced_search_form_wrapper.ssc-group-active, .search_wr_elementor.ssc-group-active');
}

/**
 * Read a named field from the theme search form.
 *
 * @param {Element|null} root Theme search wrapper.
 * @param {string} name Field name.
 * @return {string} Field value or empty string.
 */
function readThemeField(root, name) {
    if (!root) {
        return '';
    }
    const field = root.querySelector(`[name="${name}"]`);
    return field ? field.value : '';
}

/**
 * Which contact field the quote requires (Settings → Required contact).
 *
 * @return {string} 'email', 'phone' or 'both'.
 */
function contactMode() {
    const mode = typeof sscBooking !== 'undefined' ? sscBooking.contact_required : 'email';
    return mode === 'phone' || mode === 'both' ? mode : 'email';
}

const initialForm = {
    city: '',
    check_in: '',
    check_out: '',
    rooms: '1',
    guests: '2',
    male: '',
    female: '',
    budget_min: '',
    budget_max: '',
    name: '',
    email: '',
    phone: '',
    requirements: '',
    // Honeypot: bots fill it, humans never see it (server rejects non-empty).
    ssc_company: '',
};

/**
 * Smooth-scroll the Individual/Group capsule into view.
 *
 * No-op outside the homepage (standalone shortcode/block pages have no
 * capsule mount). Offset clears the sticky header, mirroring SearchMode.
 *
 * @return {void}
 */
function scrollToMode() {
    const toggle = document.querySelector('[data-ssc-mode]');
    if (!toggle) {
        return;
    }
    const sticky = document.querySelector('.header_wrapper.navbar-fixed-top');
    const offset = (sticky ? sticky.offsetHeight : 80) + 16;
    const y = toggle.getBoundingClientRect().top + window.scrollY - offset;
    requestAnimationFrame(() => {
        window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
    });
}

/**
 * POST an action to admin-ajax, refreshing a stale nonce once.
 *
 * @param {string} action AJAX action.
 * @param {Object} payload Form payload.
 * @return {Promise<Object>} Parsed JSON response.
 */
function postAction(action, payload) {
    const send = (retried) => {
        const body = new URLSearchParams();
        body.append('action', action);
        body.append('nonce', sscBooking.quote_nonce);
        Object.entries(payload).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => body.append(`${key}[]`, item));
            } else {
                body.append(key, value);
            }
        });
        return fetch(sscBooking.ajaxurl, { method: 'POST', credentials: 'same-origin', body })
            .then((response) => response.json())
            .then((res) => {
                // Cached pages carry stale nonces: refresh once and retry.
                if (res && !res.success && res.data && res.data.code === 'ssc_nonce_expired' && !retried) {
                    const refresh = new URLSearchParams();
                    refresh.append('action', 'ssc_quote_nonce');
                    return fetch(sscBooking.ajaxurl, { method: 'POST', credentials: 'same-origin', body: refresh })
                        .then((response) => response.json())
                        .then((nonceRes) => {
                            if (nonceRes && nonceRes.success && nonceRes.data && nonceRes.data.nonce) {
                                sscBooking.quote_nonce = nonceRes.data.nonce;
                                return send(true);
                            }
                            return res;
                        })
                        .catch(() => res);
                }
                return res;
            });
    };
    return send(false).catch(() => null);
}

export default function BookingForm({ title }) {
    const [form, setForm] = useState(initialForm);
    const [external, setExternal] = useState(false);
    const [phase, setPhase] = useState('search');
    const [matches, setMatches] = useState([]);
    const [total, setTotal] = useState(0);
    const [trip, setTripState] = useState(null);
    const [selected, setSelected] = useState([]);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [requestId, setRequestId] = useState(0);

    const mode = contactMode();
    const needEmail = mode === 'email' || mode === 'both';
    const needPhone = mode === 'phone' || mode === 'both';

    const set = (key) => (event) => {
        setForm((prev) => ({ ...prev, [key]: event.target.value }));
    };

    const setTrip = (key) => (event) => {
        setTripState((prev) => ({ ...prev, [key]: event.target.value }));
    };

    const tripPayload = () => {
        const themeSearch = findThemeSearch();
        const payload = {
            city: form.city,
            check_in: form.check_in,
            check_out: form.check_out,
            rooms: form.rooms,
            guests: form.guests,
            male: form.male,
            female: form.female,
            budget_min: form.budget_min,
            budget_max: form.budget_max,
            ssc_company: form.ssc_company,
        };
        if (themeSearch) {
            payload.location_text = readThemeField(themeSearch, 'search_location');
            payload.check_in = readThemeField(themeSearch, 'check_in');
            payload.check_out = readThemeField(themeSearch, 'check_out');
            const themeGuests = parseInt(readThemeField(themeSearch, 'guest_no'), 10);
            payload.guests = themeGuests > 0 ? String(themeGuests) : form.guests;
            payload.city = '';
        }
        return payload;
    };

    const suggest = () => {
        setBusy(true);
        setError('');
        const payload = tripPayload();
        postAction('ssc_group_suggest', payload).then((res) => {
            setBusy(false);
            if (res && res.success) {
                setMatches(res.data.matches || []);
                setTotal(res.data.total || 0);
                setTripState(payload);
                setPhase('results');
            } else {
                setError(
                    (res && res.data && res.data.message) ||
                        __('Something went wrong. Please try again.', 'staysuite-companion')
                );
            }
        });
    };

    const quote = () => {
        setBusy(true);
        setError('');
        const snapshot = trip || tripPayload();
        const base = external ? { ...snapshot, ...themeTrip() } : snapshot;
        postAction('ssc_group_quote', {
            ...base,
            selected_rooms: selected,
            name: form.name,
            email: form.email,
            phone: form.phone,
            requirements: form.requirements,
        }).then((res) => {
            setBusy(false);
            if (res && res.success) {
                setRequestId(res.data.request_id || 0);
                setPhase('done');
                scrollToMode();
            } else {
                setError(
                    (res && res.data && res.data.message) ||
                        __('Something went wrong. Please try again.', 'staysuite-companion')
                );
            }
        });
    };

    // Where/dates always come from the theme search above when there is
    // one; only Rooms/Guests/Budget are tweaked here.
    const themeTrip = () => {
        const themeSearch = findThemeSearch();
        if (!themeSearch) {
            return {};
        }
        return {
            location_text: readThemeField(themeSearch, 'search_location'),
            check_in: readThemeField(themeSearch, 'check_in'),
            check_out: readThemeField(themeSearch, 'check_out'),
            city: '',
        };
    };

    // Re-run suggestions from the edited trip prefs; picks reset because
    // the stay list changes.
    const refresh = () => {
        if (!trip) {
            return;
        }
        setBusy(true);
        setError('');
        postAction('ssc_group_suggest', { ...trip, ...themeTrip() }).then((res) => {
            setBusy(false);
            if (res && res.success) {
                setMatches(res.data.matches || []);
                setTotal(res.data.total || 0);
                setSelected([]);
            } else {
                setError(
                    (res && res.data && res.data.message) ||
                        __('Something went wrong. Please try again.', 'staysuite-companion')
                );
            }
        });
    };
    const suggestRef = useRef(null);
    suggestRef.current = suggest;

    useEffect(() => {
        setExternal(!!findThemeSearch());
        // The theme search submit (Individual mode's Search button is hidden
        // in group mode) routes into the suggestion step.
        const trigger = (event) => {
            event.preventDefault();
            if (suggestRef.current) {
                suggestRef.current();
            }
        };
        window.addEventListener('ssc-group-quote-submit', trigger);
        return () => window.removeEventListener('ssc-group-quote-submit', trigger);
    }, []);

    const toggleSelected = (id) => {
        setSelected((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]));
    };

    const cities = sscBooking.cities || [];

    // Red asterisk marking mandatory fields.
    const req = (
        <span className="ssc-required" aria-hidden="true">
            *
        </span>
    );

    // Channel wording follows the Required contact setting.
    const channel =
        mode === 'both'
            ? __('email or phone', 'staysuite-companion')
            : mode === 'phone'
              ? __('phone', 'staysuite-companion')
              : __('email', 'staysuite-companion');

    // One merged status line inside the contact card (never floating text).
    let statusLine = '';
    if (total === 0) {
        statusLine = sprintf(
            __('No stays matched your dates and budget — leave your details and our team will still quote you by %s.', 'staysuite-companion'),
            channel
        );
    } else if (selected.length > 0) {
        statusLine = sprintf(
            __('%d stay(s) selected — one combined quote, no payment today.', 'staysuite-companion'),
            selected.length
        );
    } else {
        statusLine = __('No stays ticked — we will quote generally for your dates and budget.', 'staysuite-companion');
    }

    if (phase === 'done') {
        return (
            <div className="ssc-booking-form-wrap">
                <div className="ssc-booking-done">
                    <h3>{__('Request received', 'staysuite-companion')}</h3>
                    {requestId > 0 && (
                        <p className="ssc-booking-ref">
                            {sprintf(__('Your reference: #%d', 'staysuite-companion'), requestId)}
                        </p>
                    )}
                    <ol>
                        <li>{__('We confirm availability for your dates and party.', 'staysuite-companion')}</li>
                        <li>
                            {needEmail
                                ? __('You get the group quote by email.', 'staysuite-companion')
                                : __('We call you with the group quote.', 'staysuite-companion')}
                        </li>
                        <li>{__('You pay the quote link — nothing is booked or charged today.', 'staysuite-companion')}</li>
                    </ol>
                </div>
            </div>
        );
    }

    return (
        <div className="ssc-booking-form-wrap">
            {title && phase === 'search' && <h2 className="ssc-booking-title">{title}</h2>}
            {phase === 'search' && (
                <form
                    className="ssc-booking-form"
                    onSubmit={(event) => {
                        event.preventDefault();
                        suggest();
                    }}
                >
                    {!external && (
                        <>
                            <label>
                                <span>{__('Where?', 'staysuite-companion')}</span>
                                <select value={form.city} onChange={set('city')}>
                                    <option value="">{__('Anywhere', 'staysuite-companion')}</option>
                                    {cities.map((city) => (
                                        <option key={city.slug} value={city.slug}>
                                            {city.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                <span>{__('Check in', 'staysuite-companion')}</span>
                                <input type="date" value={form.check_in} onChange={set('check_in')} />
                            </label>
                            <label>
                                <span>{__('Check out', 'staysuite-companion')}</span>
                                <input type="date" value={form.check_out} onChange={set('check_out')} />
                            </label>
                    <label>
                        <span>{__('Guests', 'staysuite-companion')} {req}</span>
                        <input type="number" min="1" value={form.guests} onChange={set('guests')} required />
                    </label>
                        </>
                    )}
                    <label>
                        <span>{__('Rooms', 'staysuite-companion')} {req}</span>
                        <input type="number" min="1" value={form.rooms} onChange={set('rooms')} required />
                    </label>
                    <label>
                        <span>{__('Male', 'staysuite-companion')}</span>
                        <input type="number" min="0" value={form.male} onChange={set('male')} placeholder="0" />
                    </label>
                    <label>
                        <span>{__('Female', 'staysuite-companion')}</span>
                        <input type="number" min="0" value={form.female} onChange={set('female')} placeholder="0" />
                    </label>
                    <label>
                        <span>{__('Budget min', 'staysuite-companion')}</span>
                        <input
                            type="number"
                            min="0"
                            value={form.budget_min}
                            onChange={set('budget_min')}
                            placeholder={__('Per night', 'staysuite-companion')}
                        />
                    </label>
                    <label>
                        <span>{__('Budget max', 'staysuite-companion')}</span>
                        <input
                            type="number"
                            min="0"
                            value={form.budget_max}
                            onChange={set('budget_max')}
                            placeholder={__('Per night', 'staysuite-companion')}
                        />
                    </label>
                    <button type="submit" className="ssc-booking-submit" disabled={busy}>
                        {busy ? __('Finding stays…', 'staysuite-companion') : __('Find stays', 'staysuite-companion')}
                    </button>
                    {/* Honeypot: hidden from humans, bots fill it and get rejected. */}
                    <input
                        type="text"
                        name="ssc_company"
                        value={form.ssc_company}
                        onChange={set('ssc_company')}
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        style={{ position: 'absolute', left: '-9999px', opacity: 0, height: 0 }}
                    />
                </form>
            )}
            {error && <p className="ssc-booking-error">{error}</p>}
            {phase === 'results' && (
                <div className="ssc-booking-results">
                    {total > 0 && (
                        <div className="ssc-booking-results-head">
                            <h3>{__('Suggested stays in your budget', 'staysuite-companion')}</h3>
                            <p className="ssc-booking-hint">
                                {__('Nothing is booked today — tick the stays you like and request one combined group quote.', 'staysuite-companion')}
                            </p>
                        </div>
                    )}
                    {total > 0 && (
                    <div className="ssc-booking-grid">
                        {matches.map((match) => {
                            const active = selected.includes(match.id);
                            return (
                                <article key={match.id} className={`ssc-booking-card${active ? ' ssc-selected' : ''}`}>
                                    {match.image && (
                                        <a href={match.url} target="_blank" rel="noopener noreferrer">
                                            <img src={match.image} alt="" loading="lazy" />
                                        </a>
                                    )}
                                    <h4>
                                        <a href={match.url} target="_blank" rel="noopener noreferrer">
                                            {match.title}
                                        </a>
                                    </h4>
                                    <div className="ssc-booking-card-meta">
                                        {match.was && <span className="ssc-price-was">{match.was} </span>}
                                        {[
                                            match.price && `${match.price}`,
                                            match.kind === 'hotel' && match.rooms > 0 &&
                                                sprintf(
                                                    /* translators: %d: number of matching rooms. */
                                                    _n('%d matching room', '%d matching rooms', match.rooms, 'staysuite-companion'),
                                                    match.rooms
                                                ),
                                            match.kind !== 'hotel' && match.guests > 0 &&
                                                sprintf(__('%d guests', 'staysuite-companion'), match.guests),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </div>
                                    <button
                                        type="button"
                                        className={`ssc-card-toggle${active ? ' ssc-active' : ''}`}
                                        aria-pressed={active}
                                        onClick={() => toggleSelected(match.id)}
                                    >
                                        {active
                                            ? __('Added to request', 'staysuite-companion')
                                            : __('Add to request', 'staysuite-companion')}
                                    </button>
                                </article>
                            );
                        })}
                    </div>
                    )}
                    <form
                        className="ssc-booking-form ssc-booking-contact"
                        onSubmit={(event) => {
                            event.preventDefault();
                            quote();
                        }}
                    >
                        {trip && (
                            <div className="ssc-booking-full ssc-booking-trip-edit">
                                {!external && (
                                    <label>
                                        <span>{__('Where', 'staysuite-companion')}</span>
                                        <select value={trip.city || ''} onChange={setTrip('city')}>
                                            <option value="">{__('Anywhere', 'staysuite-companion')}</option>
                                            {cities.map((city) => (
                                                <option key={city.slug} value={city.slug}>
                                                    {city.name}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                )}
                                {!external && (
                                    <label>
                                        <span>{__('Check in', 'staysuite-companion')}</span>
                                        <input type="text" value={trip.check_in || ''} onChange={setTrip('check_in')} />
                                    </label>
                                )}
                                {!external && (
                                    <label>
                                        <span>{__('Check out', 'staysuite-companion')}</span>
                                        <input type="text" value={trip.check_out || ''} onChange={setTrip('check_out')} />
                                    </label>
                                )}
                                <label>
                                    <span>{__('Rooms', 'staysuite-companion')} {req}</span>
                                    <input
                                        type="number"
                                        min="1"
                                        value={trip.rooms}
                                        onChange={setTrip('rooms')}
                                        required
                                    />
                                </label>
                                <label>
                                    <span>{__('Guests', 'staysuite-companion')} {req}</span>
                                    <input
                                        type="number"
                                        min="1"
                                        value={trip.guests}
                                        onChange={setTrip('guests')}
                                        required
                                    />
                                </label>
                                <label>
                                    <span>{__('Budget min', 'staysuite-companion')}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        value={trip.budget_min}
                                        onChange={setTrip('budget_min')}
                                        placeholder={__('Per night', 'staysuite-companion')}
                                    />
                                </label>
                                <label>
                                    <span>{__('Budget max', 'staysuite-companion')}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        value={trip.budget_max}
                                        onChange={setTrip('budget_max')}
                                        placeholder={__('Per night', 'staysuite-companion')}
                                    />
                                </label>
                                <button
                                    type="button"
                                    className="ssc-booking-refresh"
                                    onClick={refresh}
                                    disabled={busy}
                                >
                                    {busy
                                        ? __('Updating…', 'staysuite-companion')
                                        : __('Update stays', 'staysuite-companion')}
                                </button>
                            </div>
                        )}
                        <p className="ssc-booking-full ssc-booking-selection">{statusLine}</p>
                        <label>
                            <span>{__('Your name', 'staysuite-companion')} {req}</span>
                            <input type="text" value={form.name} onChange={set('name')} required />
                        </label>
                        <label>
                            <span>
                                {__('Email', 'staysuite-companion')} {needEmail ? req : ` (${__('optional', 'staysuite-companion')})`}
                            </span>
                            <input
                                type="email"
                                value={form.email}
                                onChange={set('email')}
                                required={needEmail}
                            />
                        </label>
                        <label>
                            <span>
                                {__('Phone', 'staysuite-companion')} {needPhone ? req : ` (${__('optional', 'staysuite-companion')})`}
                            </span>
                            <input type="tel" value={form.phone} onChange={set('phone')} required={needPhone} />
                        </label>
                        <label className="ssc-booking-full">
                            <span>{__('Extra requirements', 'staysuite-companion')}</span>
                            <textarea
                                rows="4"
                                value={form.requirements}
                                onChange={set('requirements')}
                                placeholder={__('Food, transport, event hall — anything we should quote for…', 'staysuite-companion')}
                            />
                        </label>
                        <button type="submit" className="ssc-booking-submit" disabled={busy}>
                            {busy
                                ? __('Sending…', 'staysuite-companion')
                                : __('Request group quote', 'staysuite-companion')}
                        </button>
                    </form>
                </div>
            )}
        </div>
    );
}
