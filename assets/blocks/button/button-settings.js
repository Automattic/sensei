/**
 * WordPress dependencies
 */
import {
	BlockAlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const MIN_BORDER_RADIUS_VALUE = 0;
const MAX_BORDER_RADIUS_VALUE = 50;

/**
 * Border radius control.
 *
 * @param {Object}   props
 * @param {number?}  props.borderRadius  Border radius attribute.
 * @param {Function} props.setAttributes Set block attributes.
 */
const BorderPanel = ( { borderRadius, setAttributes } ) => (
	<PanelBody
		title={ __( 'Border settings', 'sensei-lms' ) }
		initialOpen={ false }
	>
		<RangeControl
			label={ __( 'Border radius', 'sensei-lms' ) }
			value={ borderRadius }
			min={ MIN_BORDER_RADIUS_VALUE }
			max={ MAX_BORDER_RADIUS_VALUE }
			allowReset
			onChange={ ( value ) =>
				setAttributes( {
					borderRadius: value,
				} )
			}
		/>
	</PanelBody>
);

/**
 * Settings component for a Button block.
 *
 * @param {Object} props
 */
const ButtonSettings = ( props ) => {
	const { attributes, setAttributes } = props;
	const { borderRadius, align } = attributes;

	return (
		<>
			<BlockControls>
				<BlockAlignmentToolbar
					label={ __( 'Change button alignment', 'sensei-lms' ) }
					value={ align || props.alignmentOptions?.default }
					onChange={ ( nextAlign ) => {
						if ( nextAlign ) {
							setAttributes( { align: nextAlign } );
						}
					} }
					{ ...props.alignmentOptions }
				/>
			</BlockControls>

			<InspectorControls>
				<BorderPanel
					borderRadius={ borderRadius }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
		</>
	);
};

export default ButtonSettings;
