/**
 * StaySuite admin tab shell.
 *
 * Single settings page (menu slug ssc-staysuite). Tabs come from the
 * ssc.admin.tabs filter so Pro injects License/AI tabs without edits.
 * Go Pro renders only when Pro is absent (data-pro flag).
 */
import { createRoot } from 'react-dom/client';
import { applyFilters } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Canonical outbound links, passed from PHP (Links::all) via
 * wp_localize_script. No hard-coded URLs in JS.
 *
 * @return {Object} Link list (may be empty outside wp-admin).
 */
function sscLinks() {
    return (typeof window !== 'undefined' && window.sscLinks) || {};
}

function tabSlugFromSubmenuLink(anchor) {
    try {
        const url = new URL(anchor.href, window.location.href);
        return url.searchParams.get('tab') || 'settings';
    } catch (e) {
        return 'settings';
    }
}

function syncSidebarSubmenu(slug) {
    document.querySelectorAll('#adminmenu .wp-submenu a[href*="page=ssc-staysuite"]').forEach((anchor) => {
        const active = tabSlugFromSubmenuLink(anchor) === slug;
        anchor.classList.toggle('current', active);
        const li = anchor.closest('li');
        if (li) {
            li.classList.toggle('current', active);
        }
    });
}

function useFeedback() {
    const [feedback, setFeedback] = useState(null);
    useEffect(() => {
        if (!feedback) {
            return undefined;
        }
        const timer = window.setTimeout(() => setFeedback(null), 4500);
        return () => window.clearTimeout(timer);
    }, [feedback]);
    return { feedback, setFeedback };
}

function FeedbackToast({ feedback }) {
    if (!feedback) {
        return null;
    }
    return <div className={`ssc-feedback ssc-feedback-${feedback.kind || 'success'}`}>{feedback.text}</div>;
}

function useSettings() {
    const [settings, setSettings] = useState(null);
    const { feedback, setFeedback } = useFeedback();
    useEffect(() => {
        apiFetch({ path: '/ssc/v1/settings' }).then((res) => {
            setSettings(res && res.settings ? res.settings : {});
        }).catch((err) => {
            setFeedback({ kind: 'error', text: err && err.message ? err.message : __('Could not load settings.', 'staysuite-companion') });
        });
    }, []);
    const save = () => {
        apiFetch({ path: '/ssc/v1/settings', method: 'POST', data: { settings } }).then((res) => {
            setSettings(res && res.settings ? res.settings : settings);
            setFeedback({ kind: 'success', text: __('Settings saved.', 'staysuite-companion') });
        }).catch((err) => {
            setFeedback({ kind: 'error', text: err && err.message ? err.message : __('Could not save settings.', 'staysuite-companion') });
        });
    };
    return { settings, setSettings, save, feedback };
}

function Check({ label, checked, onChange }) {
    return (
        <label style={{ display: 'block', marginBottom: '10px' }}>
            <input type="checkbox" checked={!!checked} onChange={(e) => onChange(e.target.checked ? 1 : 0)} /> {label}
        </label>
    );
}

function Row({ label, hint, children }) {
    return (
        <div className="ssc-form-row">
            <div className="ssc-form-label">{label}</div>
            <div className="ssc-form-field">
                {children}
                {hint && <p className="description">{hint}</p>}
            </div>
        </div>
    );
}

function SettingsTab() {
    const { settings, setSettings, save, feedback } = useSettings();
    if (!settings) {
        return <p>{__('Loading…', 'staysuite-companion')}</p>;
    }
    const set = (key) => (value) => setSettings((prev) => ({ ...prev, [key]: value }));
    return (
        <div className="ssc-tab-panel">
            <h2>{__('General', 'staysuite-companion')}</h2>
            <Row label={__('Individual / Group capsule', 'staysuite-companion')}>
                <Check
                    label={__('Show the capsule above the homepage search', 'staysuite-companion')}
                    checked={settings.capsule}
                    onChange={set('capsule')}
                />
            </Row>
            <Row
                label={__('Default adults', 'staysuite-companion')}
                hint={__('Preselected in every Guests panel. 0 disables.', 'staysuite-companion')}
            >
                <input
                    type="number"
                    min="0"
                    max="6"
                    value={settings.default_adults}
                    onChange={(e) => set('default_adults')(parseInt(e.target.value || '0', 10))}
                    style={{ width: '80px' }}
                />
            </Row>
            <Row label={__('Homepage sections', 'staysuite-companion')}>
                <Check
                    label={__('Hairline dividers between sections', 'staysuite-companion')}
                    checked={settings.dividers}
                    onChange={set('dividers')}
                />
                <Check
                    label={__('Group form pop-out animation', 'staysuite-companion')}
                    checked={settings.animations}
                    onChange={set('animations')}
                />
            </Row>
            <Row label={__('Hero cover height', 'staysuite-companion')}>
                <input
                    type="number"
                    min="30"
                    max="100"
                    value={settings.hero_height}
                    onChange={(e) => set('hero_height')(parseInt(e.target.value || '75', 10))}
                    style={{ width: '80px' }}
                />{' '}
                <span>vh</span>
            </Row>
            <h2>{__('Search', 'staysuite-companion')}</h2>
            <Row
                label={__('Search results return', 'staysuite-companion')}
                hint={__('Applies to the homepage search and the advanced search. Hotels is the default.', 'staysuite-companion')}
            >
                <select
                    value={settings.search_result === 'listings' ? 'listings' : 'hotels'}
                    onChange={(e) => set('search_result')(e.target.value)}
                >
                    <option value="hotels">{__('Hotels', 'staysuite-companion')}</option>
                    <option value="listings">{__('Listings (rooms)', 'staysuite-companion')}</option>
                </select>
            </Row>
            <h2>{__('Group quotes', 'staysuite-companion')}</h2>
            <Row
                label={__('Multi-room selection', 'staysuite-companion')}
                hint={__('Shows “Add to quote” on hotel room cards so visitors can request several rooms in one group request (Pro). Off leaves instant booking only.', 'staysuite-companion')}
            >
                <Check
                    label={__('Enable group quote requests', 'staysuite-companion')}
                    checked={settings.group_selection}
                    onChange={set('group_selection')}
                />
            </Row>
            <Row
                label={__('Required contact', 'staysuite-companion')}
                hint={__('Which contact field the group quote form requires. Phone numbers are never format-checked, only required.', 'staysuite-companion')}
            >
                <select
                    value={['email', 'phone', 'both'].includes(settings.contact_required) ? settings.contact_required : 'email'}
                    onChange={(e) => set('contact_required')(e.target.value)}
                >
                    <option value="email">{__('Email only', 'staysuite-companion')}</option>
                    <option value="phone">{__('Phone only', 'staysuite-companion')}</option>
                    <option value="both">{__('Email and phone', 'staysuite-companion')}</option>
                </select>
            </Row>
            <h2>{__('Search colors', 'staysuite-companion')}</h2>
            <Row label={__('Color source', 'staysuite-companion')}>
                <label style={{ display: 'block', marginBottom: '8px' }}>
                    <input
                        type="radio"
                        checked={settings.color_mode !== 'custom'}
                        onChange={() => set('color_mode')('theme')}
                    />{' '}
                    {__('Follow theme customizer', 'staysuite-companion')}
                </label>
                <label style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <input
                        type="radio"
                        checked={settings.color_mode === 'custom'}
                        onChange={() => set('color_mode')('custom')}
                    />
                    {__('Custom', 'staysuite-companion')}
                    <input
                        type="color"
                        value={settings.color_submit}
                        onChange={(e) => set('color_submit')(e.target.value)}
                        title={__('Submit button', 'staysuite-companion')}
                    />
                    <input
                        type="color"
                        value={settings.color_hover}
                        onChange={(e) => set('color_hover')(e.target.value)}
                        title={__('Hover', 'staysuite-companion')}
                    />
                </label>
            </Row>
            <h2>{__('Advanced', 'staysuite-companion')}</h2>
            <Row
                label={__('Uninstall', 'staysuite-companion')}
                hint={__(
                    'When on, uninstalling deletes Hotels, Group Requests and all StaySuite data. Off keeps your content.',
                    'staysuite-companion'
                )}
            >
                <Check
                    label={__('Delete all StaySuite data when the plugin is uninstalled', 'staysuite-companion')}
                    checked={settings.delete_on_uninstall}
                    onChange={set('delete_on_uninstall')}
                />
            </Row>
            <div className="ssc-form-actions">
                <button type="button" className="button button-primary" onClick={save}>
                    {__('Save settings', 'staysuite-companion')}
                </button>
            </div>
            <FeedbackToast feedback={feedback} />
        </div>
    );
}

const PRO_FEATURES = [
    __('Multi-room selection with combined pricing summary', 'staysuite-companion'),
    __('Quote-to-invoice pipeline with deposits and reminders', 'staysuite-companion'),
    __('Seasonal pricing display and scheduled sales', 'staysuite-companion'),
    __('OTA availability sync (Booking.com, Airbnb)', 'staysuite-companion'),
    __('AI group concierge on your own API key', 'staysuite-companion'),
    __('Analytics, upsell add-ons and bulk importers', 'staysuite-companion'),
];

function GoProTab() {
    const links = sscLinks();
    return (
        <div className="ssc-tab-panel">
            <h2>{__('StaySuite Pro', 'staysuite-companion')}</h2>
            <p>{__('Turn browsing into high-value bookings:', 'staysuite-companion')}</p>
            <ul className="ssc-pro-list">
                {PRO_FEATURES.map((feature) => (
                    <li key={feature}>{feature}</li>
                ))}
            </ul>
            <p>
                <a className="button button-primary button-hero" href={links.pro} target="_blank" rel="noopener noreferrer">
                    {__('Get StaySuite Pro', 'staysuite-companion')}
                </a>
            </p>
        </div>
    );
}

function AdminApp({ logo, tabs, initial }) {
    const [active, setActive] = useState(initial || (tabs.length ? tabs[0].slug : ''));
    const current = tabs.find((t) => t.slug === active) || tabs[0];
    const links = sscLinks();
    const select = (slug) => {
        setActive(slug);
        updateTabUrl(slug);
    };

    useEffect(() => syncSidebarSubmenu(active), [active]);

    useEffect(() => {
        const onNavigate = (event) => {
            if (!event.detail || typeof event.detail.slug !== 'string') {
                return;
            }
            setActive(event.detail.slug);
            updateTabUrl(event.detail.slug);
        };
        window.addEventListener('ssc-tab-navigate', onNavigate);
        return () => window.removeEventListener('ssc-tab-navigate', onNavigate);
    }, []);

    function updateTabUrl(slug) {
        try {
            const url = new URL(location.href);
            url.searchParams.set('tab', slug);
            history.replaceState(null, '', url.toString());
        } catch (e) {
            /* non-fatal */
        }
    }
    return (
        <div className="ssc-admin">
            <div className="ssc-admin-head">
                {logo && <img src={logo} alt="StaySuite" />}
                <h1>StaySuite</h1>
                <span className="ssc-admin-links">
                    <a href={links.docs} target="_blank" rel="noopener noreferrer">
                        {__('Docs', 'staysuite-companion')}
                    </a>
                    <a href={links.support} target="_blank" rel="noopener noreferrer">
                        {__('Support', 'staysuite-companion')}
                    </a>
                </span>
            </div>
            <div className="ssc-admin-tabs" role="tablist">
                {tabs.map((tab) => (
                    <button
                        key={tab.slug}
                        type="button"
                        role="tab"
                        aria-selected={tab.slug === active}
                        className={tab.slug === active ? 'ssc-tab-active' : ''}
                        onClick={() => select(tab.slug)}
                    >
                        {tab.title}
                    </button>
                ))}
            </div>
            <div className="ssc-admin-body">
                {current && current.render ? <current.render /> : null}
            </div>
        </div>
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('ssc-admin-root');
    if (!root) {
        return;
    }
    const proActive = root.dataset.pro === '1';
    const base = [{ slug: 'settings', title: __('Settings', 'staysuite-companion'), render: SettingsTab }];
    if (!proActive) {
        base.push({ slug: 'go-pro', title: __('Go Pro', 'staysuite-companion'), render: GoProTab });
    }
    const tabs = applyFilters('ssc.admin.tabs', base);
    document
        .querySelectorAll('#adminmenu .wp-submenu a[href*="page=ssc-staysuite"]')
        .forEach((anchor) => {
            anchor.addEventListener('click', (event) => {
                event.preventDefault();
                const slug = tabSlugFromSubmenuLink(anchor);
                window.dispatchEvent(new CustomEvent('ssc-tab-navigate', { detail: { slug } }));
            });
        });
    const slugs = tabs.map((t) => t.slug);
    let initial = '';
    try {
        initial = new URLSearchParams(location.search).get('tab') || '';
    } catch (e) {
        initial = '';
    }
    if (!slugs.includes(initial)) {
        initial = slugs.length ? slugs[0] : '';
    }
    createRoot(root).render(<AdminApp logo={root.dataset.logo || ''} tabs={tabs} initial={initial} />);
});
