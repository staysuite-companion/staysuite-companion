/**
 * Term tablets block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

const TAXONOMIES = [
    { label: __('Cities', 'staysuite-companion'), value: 'property_city' },
    { label: __('Categories', 'staysuite-companion'), value: 'property_category' },
    { label: __('Listing types', 'staysuite-companion'), value: 'property_action_category' },
    { label: __('Areas', 'staysuite-companion'), value: 'property_area' },
];

registerBlockType('ssc/term-tablets', {
    title: __('StaySuite Term Tablets', 'staysuite-companion'),
    icon: 'tag',
    category: 'widgets',
    attributes: {
        taxonomy: { type: 'string', default: 'property_city' },
        number: { type: 'number', default: 6 },
        hide_empty: { type: 'boolean', default: true },
        show_divider: { type: 'boolean', default: true },
    },
    edit({ attributes, setAttributes }) {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Tablets settings', 'staysuite-companion')}>
                        <SelectControl
                            label={__('Source', 'staysuite-companion')}
                            value={attributes.taxonomy}
                            options={TAXONOMIES}
                            onChange={(taxonomy) => setAttributes({ taxonomy })}
                        />
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
