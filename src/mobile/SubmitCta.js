/**
 * Mobile Submit Property call-to-action.
 *
 * The theme prints #submit_action only in the desktop header; the mobile
 * drawer's logged-out view has login/register forms but no listing CTA.
 * This prepends the equivalent link for logged-out visitors, honoring
 * the theme's own show-submit option (see sscSubmitCta, localized from
 * wp_estate_show_submit). Logged-in owners already have Add New Listing
 * in the drawer, so they are skipped server-side.
 */

function submitCtaConfig() {
    const cfg = (typeof window !== 'undefined' && window.sscSubmitCta) || {};
    return {
        show: cfg.show === 1 || cfg.show === '1',
        url: typeof cfg.url === 'string' ? cfg.url : '',
        label: typeof cfg.label === 'string' && cfg.label ? cfg.label : 'Submit Property',
    };
}

export default function mountSubmitCta() {
    const config = submitCtaConfig();
    if (!config.show || !config.url) {
        return;
    }
    if (document.body.classList.contains('logged-in')) {
        return;
    }
    const drawer = document.querySelector('#mobilewrapperuser .snap-drawer-right');
    if (!drawer || drawer.querySelector('[data-ssc-submit]')) {
        return;
    }
    const anchor = drawer.querySelector('.login_sidebar_mobile, .login_form');
    if (!anchor) {
        return;
    }
    const link = document.createElement('a');
    link.href = config.url;
    link.className = 'ssc-mobile-submit';
    link.setAttribute('data-ssc-submit', '1');
    link.textContent = config.label;
    anchor.before(link);
}
