import { useState } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';

import { LessonActionsBlockSettings } from './settings';

const allowedBlocks = [
	'sensei-lms/button-complete-lesson',
	'sensei-lms/button-next-lesson',
	'sensei-lms/button-reset-lesson',
];
const innerBlocksTemplate = allowedBlocks.map( ( blockName ) => [
	blockName,
	{
		inContainer: true,
		...( 'sensei-lms/button-complete-lesson' === blockName && {
			align: 'full',
		} ),
	},
] );

/**
 * Edit lesson actions block component.
 *
 * @param {Object}   props
 * @param {string}   props.className               Custom class name.
 * @param {string}   props.clientId                Block ID.
 * @param {Function} props.setAttributes           Block set attributes function.
 * @param {Object}   props.attributes              Block attributes.
 * @param {Object}   props.attributes.activeBlocks Active blocks, where the key is the block name.
 */
const EditLessonActionsBlock = ( {
	className,
	clientId,
	setAttributes,
	attributes: { activeBlocks },
} ) => {
	const block = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId ),
		[]
	);
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const [ blocksAttributes, setBlocksAttributes ] = useState( {} );

	/**
	 * Toggle block.
	 *
	 * @param {string} blockName Block name.
	 *
	 * @return {Function} Function to toggle the block.
	 */
	const toggleBlock = ( blockName ) => ( on ) => {
		const toggledBlock = block.innerBlocks.find(
			( i ) => i.name === blockName
		);
		let newBlocks = null;

		if ( on && ! toggledBlock ) {
			// Add block.
			newBlocks = [
				...block.innerBlocks,
				createBlock( blockName, blocksAttributes[ blockName ] || {} ),
			];
		} else if ( ! on && toggledBlock ) {
			// Remove block.
			newBlocks = block.innerBlocks.filter(
				( i ) => i.name !== blockName
			);

			// Save block attributes to restore, if needed.
			setBlocksAttributes( ( attrs ) => ( {
				...attrs,
				[ blockName ]: toggledBlock.attributes,
			} ) );
		}

		if ( newBlocks ) {
			replaceInnerBlocks( clientId, newBlocks, false );
		}

		setAttributes( {
			activeBlocks: { ...activeBlocks, [ blockName ]: on },
		} );
	};

	// Filter inner blocks based on the settings.
	const filteredInnerBlocksTemplate = innerBlocksTemplate.filter(
		( i ) => false !== activeBlocks[ i[ 0 ] ]
	);

	return (
		<div className={ className }>
			<div className="sensei-buttons-container">
				<LessonActionsBlockSettings
					activeBlocks={ activeBlocks }
					toggleBlock={ toggleBlock }
				/>
				<InnerBlocks
					allowedBlocks={ allowedBlocks }
					template={ filteredInnerBlocksTemplate }
					templateLock="all"
				/>
			</div>
		</div>
	);
};

export default EditLessonActionsBlock;
