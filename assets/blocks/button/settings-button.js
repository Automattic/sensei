import {
	AlignmentToolbar,
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
export const BorderPanel = ( { borderRadius, setAttributes } ) => {
	return (
		<PanelBody title={ __( 'Border settings', 'sensei-lms' ) }>
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
};

/**
 * Settings component for a Button block.
 *
 * @param {Object} props
 */
export const ButtonBlockSettings = ( props ) => {
	const { attributes, setAttributes } = props;
	const { borderRadius, align } = attributes;
	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( nextAlign ) => {
						setAttributes( { align: nextAlign } );
					} }
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
