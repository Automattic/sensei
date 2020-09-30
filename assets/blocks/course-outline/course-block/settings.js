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
export const OutlineBlockSettings = ( {
	animationsEnabled,
	setAnimationsEnabled,
} ) => (
	<InspectorControls>
		<PanelBody
			title={ __( 'Animation', 'sensei-lms' ) }
			initialOpen={ false }
		>
			<ToggleControl
				checked={ animationsEnabled }
				onChange={ setAnimationsEnabled }
				label={ __(
					'Animate the expanding and collapsing of modules',
					'sensei-lms'
				) }
			/>
		</PanelBody>
	</InspectorControls>
);
