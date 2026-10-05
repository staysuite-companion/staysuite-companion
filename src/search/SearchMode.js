/**
 * Individual / Group switch mounted above a theme search bar.
 *
 * Group mode keeps the search bar visible (its submit is hidden by CSS
 * and routed into the quote flow) and portals the group quote form to a
 * sibling node AFTER the cover, so the cover never stretches and the
 * form spans the content width.
 */
import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { __ } from '@wordpress/i18n';
import BookingForm from '../booking/BookingForm';

export default function SearchMode({ wrapper }) {
    const [mode, setMode] = useState('individual');
    const [portalNode, setPortalNode] = useState(null);

    useEffect(() => {
        if (!wrapper) {
            return undefined;
        }
        wrapper.classList.toggle('ssc-group-active', mode === 'group');
        if (mode !== 'group') {
            return undefined;
        }
        // Route the theme search submit into the stays suggestion step.
        const form = wrapper.querySelector('form');
        if (!form) {
            return undefined;
        }
        const intercept = (event) => {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('ssc-group-quote-submit'));
        };
        form.addEventListener('submit', intercept);
        return () => form.removeEventListener('submit', intercept);
    }, [mode, wrapper]);

    useEffect(() => {
        if (mode !== 'group' || !wrapper) {
            return undefined;
        }
        const hero = wrapper.closest('.ssc-hero');
        const host = hero && hero.parentNode ? hero.parentNode : wrapper.parentNode;
        if (!host) {
            return undefined;
        }
        const node = document.createElement('div');
        node.className = 'ssc-mode-form-outside';
        if (hero && hero.nextSibling) {
            host.insertBefore(node, hero.nextSibling);
        } else {
            host.appendChild(node);
        }
        setPortalNode(node);
        // Trigger the pop-out animation after the collapsed state commits.
        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                if (node.isConnected) {
                    node.classList.add('ssc-open');
                }
            })
        );
        // Dock the Individual/Group capsule just below the header so the
        // search and the whole group form land in view together.
        const previous = wrapper.previousElementSibling;
        const toggle =
            previous && previous.hasAttribute('data-ssc-mode')
                ? previous
                : document.querySelector('[data-ssc-mode]');
        setTimeout(() => {
            if (!toggle) {
                return;
            }
            const sticky = document.querySelector('.header_wrapper.navbar-fixed-top');
            const offset = (sticky ? sticky.offsetHeight : 80) + 16;
            const y = toggle.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
        }, 80);
        return () => {
            node.remove();
            setPortalNode(null);
        };
    }, [mode, wrapper]);

    return (
        <div className="ssc-mode">
            <div className="ssc-mode-toggle" role="tablist" aria-label={__('Booking type', 'staysuite-companion')}>
                <button
                    type="button"
                    role="tab"
                    aria-selected={mode === 'individual'}
                    className={mode === 'individual' ? 'ssc-mode-active' : ''}
                    onClick={() => setMode('individual')}
                >
                    {__('Individual', 'staysuite-companion')}
                </button>
                <button
                    type="button"
                    role="tab"
                    aria-selected={mode === 'group'}
                    className={mode === 'group' ? 'ssc-mode-active' : ''}
                    onClick={() => setMode('group')}
                >
                    {__('Group', 'staysuite-companion')}
                </button>
            </div>
            {mode === 'group' && portalNode &&
                createPortal(
                    <div className="ssc-mode-form">
                        <BookingForm />
                    </div>,
                    portalNode
                )}
        </div>
    );
}
