/**
 * Term tablets block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

const TAXONOMIES = [
    { label: __('Cities', 'staysuite-companion'), value: 'property_city' },
    { label: __('Categories', 'staysuite-companion'), value: 'property_category' },
    { label: __('Listing types', 'staysuite-companion'), value: 'property_action_category' },
    { label: __('Areas', 'staysuite-companion'), value: 'property_area' },
];

/**
 * Term options for the selected taxonomy, via the shared options route.
 * The theme's taxonomies are not REST-exposed, so the editor cannot use
 * /wp/v2 for this dropdown.
 *
 * @param {string} taxonomy Selected taxonomy slug.
 * @return {Array} Term options with slug and name.
 */
function useTermOptions(taxonomy) {
    const [terms, setTerms] = useState([]);
    useEffect(() => {
        let active = true;
        const path = `/ssc/v1/carousel-options?taxonomy=${encodeURIComponent(taxonomy || '')}&source=rooms`;
        apiFetch({ path })
            .then((res) => {
                if (!active) {
                    return;
                }
                setTerms(Array.isArray(res && res.terms) ? res.terms : []);
            })
            .catch(() => {
                if (active) {
                    setTerms([]);
                }
            });
        return () => {
            active = false;
        };
    }, [taxonomy]);
    return terms;
}

registerBlockType('ssc/term-tablets', {
    title: __('StaySuite Term Tablets', 'staysuite-companion'),
    icon: 'tag',
    category: 'widgets',
    attributes: {
        taxonomy: { type: 'string', default: 'property_city' },
        number: { type: 'number', default: 6 },
        hide_empty: { type: 'boolean', default: true },
        include_slugs: { type: 'array', default: [] },
        show_divider: { type: 'boolean', default: true },
    },
    edit({ attributes, setAttributes }) {
        const terms = useTermOptions(attributes.taxonomy);
        const picked = Array.isArray(attributes.include_slugs) ? attributes.include_slugs : [];
        const hasHandPicked = picked.length > 0;
        // The attribute stores slugs; names are display-only.
        const nameBySlug = {};
        terms.forEach((t) => {
            if (!nameBySlug[t.slug]) {
                nameBySlug[t.slug] = t.name;
            }
        });
        const removeSlug = (slug) => setAttributes({ include_slugs: picked.filter((s) => s !== slug) });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Tablets settings', 'staysuite-companion')}>
                        <SelectControl
                            label={__('Source', 'staysuite-companion')}
                            value={attributes.taxonomy}
                            options={TAXONOMIES}
                            onChange={(taxonomy) => setAttributes({ taxonomy, include_slugs: [] })}
                        />
                        <SelectControl
                            label={__('Hand-picked terms', 'staysuite-companion')}
                            help={__('Pick terms one at a time from the dropdown. Shows only these, in the order picked; the count and empty settings below are hidden while set.', 'staysuite-companion')}
                            value=""
                            options={[
                                { label: __('Choose a term to add…', 'staysuite-companion'), value: '' },
                                ...terms
                                    .filter((t) => !picked.includes(t.slug))
                                    .map((t) => ({ label: t.name, value: t.slug })),
                            ]}
                            onChange={(slug) => {
                                if (slug !== '' && !picked.includes(slug)) {
                                    setAttributes({ include_slugs: [...picked, slug] });
                                }
                            }}
                        />
                        {hasHandPicked && (
                            <ul style={{ listStyle: 'none', margin: '0 0 16px' }}>
                                {picked.map((slug) => (
                                    <li
                                        key={slug}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'space-between',
                                            gap: '8px',
                                            padding: '4px 0',
                                        }}
                                    >
                                        <span>{nameBySlug[slug] || slug}</span>
                                        <Button
                                            isSmall
                                            isLink
                                            isDestructive
                                            onClick={() => removeSlug(slug)}
                                        >
                                            {__('Remove', 'staysuite-companion')}
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {!hasHandPicked && (
                            <>
                                <RangeControl
                                    label={__('How many', 'staysuite-companion')}
                                    value={attributes.number}
                                    min={1}
                                    max={24}
                                    onChange={(number) => setAttributes({ number })}
                                />
                                <ToggleControl
                                    label={__('Hide empty', 'staysuite-companion')}
                                    checked={attributes.hide_empty}
                                    onChange={(hide_empty) => setAttributes({ hide_empty })}
                                />
                            </>
                        )}
                        <ToggleControl
                            label={__('Hairline divider below', 'staysuite-companion')}
                            checked={attributes.show_divider}
                            onChange={(show_divider) => setAttributes({ show_divider })}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('Custom gradient palettes', 'staysuite-companion'), __('Sponsored tablet placement', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/term-tablets" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
