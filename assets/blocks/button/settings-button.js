import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
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
};

/**
 * Settings component for a Button block.
 *
 * @param {Object} props
 */
export const ButtonBlockSettings = ( props ) => {
	const { attributes, setAttributes } = props;
	const { borderRadius, textAlign } = attributes;

	const themeSupports = useSelect( ( select ) =>
		select( 'core' ).getThemeSupports()
	);

	return (
		<>
			{ themeSupports[ 'align-wide' ] && (
				<BlockControls>
					<AlignmentToolbar
						value={ textAlign }
						onChange={ ( value ) => {
							setAttributes( { textAlign: value } );
						} }
					/>
				</BlockControls>
			) }

			<InspectorControls>
				<BorderPanel
					borderRadius={ borderRadius }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
		</>
	);
};
