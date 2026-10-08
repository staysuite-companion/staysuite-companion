/**
 * Listing carousel block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

function useCarouselOptions(taxonomy, source) {
    const [options, setOptions] = useState({ terms: [], posts: [] });
    useEffect(() => {
        let active = true;
        const path = `/ssc/v1/carousel-options?taxonomy=${encodeURIComponent(taxonomy || '')}&source=${encodeURIComponent(source || 'rooms')}`;
        apiFetch({ path })
            .then((res) => {
                if (!active) {
                    return;
                }
                setOptions({
                    terms: Array.isArray(res && res.terms) ? res.terms : [],
                    posts: Array.isArray(res && res.posts) ? res.posts : [],
                });
            })
            .catch(() => {
                if (active) {
                    setOptions({ terms: [], posts: [] });
                }
            });
        return () => {
            active = false;
        };
    }, [taxonomy, source]);
    return options;
}

registerBlockType('ssc/listing-carousel', {
    title: __('StaySuite Listing Carousel', 'staysuite-companion'),
    icon: 'slides',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: '' },
        source: { type: 'string', default: 'rooms' },
        taxonomy: { type: 'string', default: '' },
        term: { type: 'string', default: '' },
        city: { type: 'string', default: '' },
        count: { type: 'number', default: 8 },
        featured_only: { type: 'boolean', default: false },
        include_ids: { type: 'string', default: '' },
        order: { type: 'string', default: 'featured' },
        show_divider: { type: 'boolean', default: true },
    },
    edit({ attributes, setAttributes }) {
        const set = (key) => (value) => setAttributes({ [key]: value });
        const { terms, posts } = useCarouselOptions(attributes.taxonomy, attributes.source);
        const hasHandPicked = attributes.include_ids !== '';

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Row settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Row title', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={set('title')}
                        />
                        <SelectControl
                            label={__('Source', 'staysuite-companion')}
                            value={attributes.source}
                            options={[
                                { label: __('Rooms', 'staysuite-companion'), value: 'rooms' },
                                { label: __('Hotels', 'staysuite-companion'), value: 'hotels' },
                            ]}
                            onChange={(value) => setAttributes({ source: value, include_ids: '' })}
                        />
                        <SelectControl
                            label={__('Hand-picked', 'staysuite-companion')}
                            help={__('Optional single post from the selected source. Shows only this post; the filters below are hidden while set.', 'staysuite-companion')}
                            value={attributes.include_ids}
                            options={[
                                { label: __('None', 'staysuite-companion'), value: '' },
                                ...posts.map((p) => ({ label: p.title, value: String(p.id) })),
                            ]}
                            onChange={set('include_ids')}
                        />
                        {!hasHandPicked && (
                            <>
                                <SelectControl
                                    label={__('Filter taxonomy', 'staysuite-companion')}
                                    value={attributes.taxonomy}
                                    options={[
                                        { label: __('None', 'staysuite-companion'), value: '' },
                                        { label: __('City', 'staysuite-companion'), value: 'property_city' },
                                        { label: __('Category', 'staysuite-companion'), value: 'property_category' },
                                        { label: __('Listing type', 'staysuite-companion'), value: 'property_action_category' },
                                        { label: __('Area', 'staysuite-companion'), value: 'property_area' },
                                    ]}
                                    onChange={(value) => setAttributes({ taxonomy: value, term: '' })}
                                />
                                {attributes.taxonomy && (
                                    <SelectControl
                                        label={__('Term', 'staysuite-companion')}
                                        help={__('Choose from the selected taxonomy. Works for both rooms and hotels.', 'staysuite-companion')}
                                        value={attributes.term}
                                        options={[
                                            { label: __('All terms', 'staysuite-companion'), value: '' },
                                            ...terms.map((t) => ({ label: `${t.name} (${t.count})`, value: t.slug })),
                                        ]}
                                        onChange={set('term')}
                                    />
                                )}
                                <RangeControl
                                    label={__('How many', 'staysuite-companion')}
                                    value={attributes.count}
                                    min={1}
                                    max={24}
                                    onChange={set('count')}
                                />
                                <ToggleControl
                                    label={__('Featured only', 'staysuite-companion')}
                                    checked={attributes.featured_only}
                                    onChange={set('featured_only')}
                                />
                                <SelectControl
                                    label={__('Order', 'staysuite-companion')}
                                    value={attributes.order}
                                    options={[
                                        { label: __('Featured first', 'staysuite-companion'), value: 'featured' },
                                        { label: __('Price: low to high', 'staysuite-companion'), value: 'price_asc' },
                                        { label: __('Price: high to low', 'staysuite-companion'), value: 'price_desc' },
                                        { label: __('Newest', 'staysuite-companion'), value: 'newest' },
                                        { label: __('Random', 'staysuite-companion'), value: 'rand' },
                                    ]}
                                    onChange={set('order')}
                                />
                            </>
                        )}
                        <ToggleControl
                            label={__('Hairline divider below', 'staysuite-companion')}
                            checked={attributes.show_divider}
                            onChange={set('show_divider')}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('Discount badges + scheduled sales', 'staysuite-companion'), __('Sponsored ordering', 'staysuite-companion'), __('Multi-room Add buttons', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/listing-carousel" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
