/**
 * Hero search block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, SelectControl, Button, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

registerBlockType('ssc/hero-search', {
    title: __('StaySuite Hero Search', 'staysuite-companion'),
    icon: 'cover-image',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: '' },
        subtitle: { type: 'string', default: '' },
        image_id: { type: 'number', default: 0 },
        show_search: { type: 'boolean', default: true },
        search_mode: { type: 'string', default: 'theme' },
        hero_height: { type: 'number', default: 75 },
        show_capsule: { type: 'boolean', default: true },
        animate_form: { type: 'boolean', default: true },
    },
    edit({ attributes, setAttributes }) {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Hero settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Heading', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                        <TextControl
                            label={__('Subheading', 'staysuite-companion')}
                            value={attributes.subtitle}
                            onChange={(subtitle) => setAttributes({ subtitle })}
                        />
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                value={attributes.image_id}
                                onSelect={(media) => setAttributes({ image_id: media.id })}
                                render={({ open }) => (
                                    <div style={{ marginBottom: '16px' }}>
                                        <Button variant="secondary" onClick={open}>
                                            {attributes.image_id
                                                ? __('Change cover image', 'staysuite-companion')
                                                : __('Pick cover image', 'staysuite-companion')}
                                        </Button>
                                    </div>
                                )}
                            />
                        </MediaUploadCheck>
                        <ToggleControl
                            label={__('Show search form', 'staysuite-companion')}
                            checked={attributes.show_search}
                            onChange={(show_search) => setAttributes({ show_search })}
                        />
                        <SelectControl
                            label={__('Search type', 'staysuite-companion')}
                            value={attributes.search_mode}
                            options={[
                                { label: __('Theme search (with Group pill)', 'staysuite-companion'), value: 'theme' },
                                { label: __('Simple search', 'staysuite-companion'), value: 'simple' },
                                { label: __('No search', 'staysuite-companion'), value: 'none' },
                            ]}
                            onChange={(search_mode) => setAttributes({ search_mode })}
                        />
                        <RangeControl
                            label={__('Cover height (vh)', 'staysuite-companion')}
                            value={attributes.hero_height}
                            min={30}
                            max={100}
                            onChange={(hero_height) => setAttributes({ hero_height })}
                        />
                        <ToggleControl
                            label={__('Show Individual / Group capsule', 'staysuite-companion')}
                            checked={attributes.show_capsule}
                            onChange={(show_capsule) => setAttributes({ show_capsule })}
                        />
                        <ToggleControl
                            label={__('Group form pop-out animation', 'staysuite-companion')}
                            checked={attributes.animate_form}
                            onChange={(animate_form) => setAttributes({ animate_form })}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('AI concierge search', 'staysuite-companion'), __('Scheduled cover variants', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/hero-search" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
