/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.bordered      Whether the border is enabled
 * @param {Function} props.setBordered   Callback to enable the border.
 * @param {string}   props.customSlug    Custom slug for the module.
 * @param {Function} props.setCustomSlug Callback to set the custom slug.
 */
const ModuleSettings = ( {
	bordered,
	setBordered,
	customSlug,
	setCustomSlug,
} ) => (
	<InspectorControls>
		<PanelBody
			title={ __( 'Border settings', 'sensei-lms' ) }
			initialOpen={ false }
		>
			<ToggleControl
				checked={ bordered }
				onChange={ setBordered }
				label={ __( 'Border', 'sensei-lms' ) }
				help={ __( 'Toggle to enable the border.', 'sensei-lms' ) }
			/>
		</PanelBody>
		<PanelBody
			title={ __( 'Custom slug', 'sensei-lms' ) }
			initialOpen={ customSlug || false }
		>
			<TextControl
				className="wp-block-sensei-lms-course-outline-module__slug-input"
				placeholder={ __(
					'Optional, keep empty for default',
					'sensei-lms'
				) }
				value={ customSlug }
				onChange={ setCustomSlug }
			/>
		</PanelBody>
	</InspectorControls>
);

export default ModuleSettings;
