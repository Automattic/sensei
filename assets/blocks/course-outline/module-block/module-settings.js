/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.bordered    Whether the border is enabled
 * @param {Function} props.setBordered Callback to enable the border.
 */
const ModuleSettings = ( { bordered, setBordered } ) => (
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
	</InspectorControls>
);

export default ModuleSettings;
