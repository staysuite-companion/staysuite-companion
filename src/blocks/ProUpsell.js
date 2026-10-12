/**
 * Shared Pro upsell panel for block inspectors.
 *
 * Tasteful and wp.org-safe: locked feature list plus an outbound link.
 * Never gates existing free functionality.
 */
import { PanelBody, Button } from '@wordpress/components';
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

export default function ProUpsell({ features }) {
    const links = sscLinks();
    if (links.pro_active) {
        return null;
    }
    return (
        <PanelBody title={__('StaySuite Pro', 'staysuite-companion')} initialOpen={false}>
            <ul style={{ listStyle: 'disc', marginLeft: '18px' }}>
                {(features || []).map((feature) => (
                    <li key={feature}>{feature}</li>
                ))}
            </ul>
            <Button
                variant="primary"
                href={links.pro}
                target="_blank"
                rel="noopener noreferrer"
                style={{ marginTop: '8px' }}
            >
                {__('Get StaySuite Pro', 'staysuite-companion')}
            </Button>
        </PanelBody>
    );
}
