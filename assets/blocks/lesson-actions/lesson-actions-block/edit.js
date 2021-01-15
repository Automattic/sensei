import { useState } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { LessonActionsBlockSettings } from './settings';

const ALLOWED_BLOCKS = [
	'sensei-lms/button-complete-lesson',
	'sensei-lms/button-next-lesson',
	'sensei-lms/button-reset-lesson',
];
const INNER_BLOCKS_TEMPLATE = ALLOWED_BLOCKS.map( ( blockName ) => [
	blockName,
	{
		inContainer: true,
		...( 'sensei-lms/button-complete-lesson' === blockName && {
			align: 'full',
		} ),
	},
] );

/**
 * Toggle blocks hook.
 *
 * @param {Object}   options                Hook options.
 * @param {string}   options.parentClientId Parent client ID.
 * @param {Function} options.setAttributes  Set attributes function.
 * @param {Object}   options.toggledBlocks  Toggled blocks, where the key is the block name.
 * @param {Object[]} options.blocks         Blocks to prepare to toggle.
 *
 * @return {Object[]} Blocks prepared to toggle.
 */
const useToggleBlocks = ( {
	parentClientId,
	setAttributes,
	toggledBlocks,
	blocks,
} ) => {
	const parentBlock = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( parentClientId ),
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
		const toggledBlock = parentBlock.innerBlocks.find(
			( i ) => i.name === blockName
		);
		let newBlocks = null;

		if ( on && ! toggledBlock ) {
			// Add block using the previous attributes if it exists.
			newBlocks = [
				...parentBlock.innerBlocks,
				createBlock( blockName, blocksAttributes[ blockName ] || {} ),
			];
		} else if ( ! on && toggledBlock ) {
			// Remove block.
			newBlocks = parentBlock.innerBlocks.filter(
				( i ) => i.name !== blockName
			);

			// Save block attributes to restore, if needed.
			setBlocksAttributes( ( attrs ) => ( {
				...attrs,
				[ blockName ]: toggledBlock.attributes,
			} ) );
		}

		if ( newBlocks ) {
			replaceInnerBlocks( parentClientId, newBlocks, false );
		}

		setAttributes( {
			toggledBlocks: { ...toggledBlocks, [ blockName ]: on },
		} );
	};

	return blocks.map( ( block ) => ( {
		active: false !== toggledBlocks[ block.blockName ],
		onToggle: toggleBlock( block.blockName ),
		label: block.label,
	} ) );
};

/**
 * Edit lesson actions block component.
 *
 * @param {Object}   props
 * @param {string}   props.className                Custom class name.
 * @param {string}   props.clientId                 Block ID.
 * @param {Function} props.setAttributes            Block set attributes function.
 * @param {Object}   props.attributes               Block attributes.
 * @param {Object}   props.attributes.toggledBlocks Toggled blocks, where the key is the block name.
 */
const EditLessonActionsBlock = ( {
	className,
	clientId,
	setAttributes,
	attributes: { toggledBlocks },
} ) => {
	const toggleBlocks = useToggleBlocks( {
		parentClientId: clientId,
		setAttributes,
		toggledBlocks,
		blocks: [
			{
				blockName: 'sensei-lms/button-reset-lesson',
				label: __( 'Reset lesson', 'sensei-lms' ),
			},
		],
	} );

	// Filter inner blocks based on the settings.
	const filteredInnerBlocksTemplate = INNER_BLOCKS_TEMPLATE.filter(
		( i ) => false !== toggledBlocks[ i[ 0 ] ]
	);

	return (
		<div className={ className }>
			<div className="sensei-buttons-container">
				<LessonActionsBlockSettings toggleBlocks={ toggleBlocks } />
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ filteredInnerBlocksTemplate }
					templateLock="all"
				/>
			</div>
		</div>
	);
};

export default EditLessonActionsBlock;
