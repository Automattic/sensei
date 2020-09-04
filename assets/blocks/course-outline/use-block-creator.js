import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

const blockNames = {
	module: 'sensei-lms/course-outline-module',
	lesson: 'sensei-lms/course-outline-lesson',
};

/**
 * Blocks creator hook.
 * It adds blocks dynamically to the InnerBlock.
 *
 * @param {Object[]} blocksData Blocks data to insert.
 * @param {string}   clientId   Block client ID.
 */
const useBlocksCreator = ( blocksData, clientId ) => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		if ( ! blocksData || 0 === blocksData.length ) {
			return;
		}

		const blocks = blocksData.map( ( { type, ...block } ) =>
			createBlock( blockNames[ type ], block )
		);

		replaceInnerBlocks( clientId, blocks, false );
	}, [ blocksData, clientId, replaceInnerBlocks ] );
};

export default useBlocksCreator;
