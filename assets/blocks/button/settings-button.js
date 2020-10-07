import {
	AlignmentToolbar,
	BlockControls,
} from '@wordpress/block-editor';

/**
 * Settings component for a Button block.
 *
 * @param {Object} props
 */
export const ButtonBlockSettings = ( props ) => {
	const { attributes, setAttributes } = props;
	const { align } = attributes;
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
		</>
	);
};
