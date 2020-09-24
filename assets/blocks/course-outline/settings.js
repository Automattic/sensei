import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Inspector controls for outline block.
 *
 * @param {Object}   props
 * @param {boolean}  props.animationsEnabled    Whether animations are enabled.
 * @param {Function} props.setAnimationsEnabled Callback to be called when animations enabling is updated.
 */
export function OutlineBlockSettings( {
	animationsEnabled,
	setAnimationsEnabled,
} ) {
	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Enable Animations', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					checked={ animationsEnabled }
					onChange={ setAnimationsEnabled }
					label={ __(
						'Enable animations on module collapse/expand.',
						'sensei-lms'
					) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}
