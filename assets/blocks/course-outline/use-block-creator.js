import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

const blockNames = {
	module: 'sensei-lms/course-outline-module',
	lesson: 'sensei-lms/course-outline-lesson',
};

const useBlocksCreator = ( data, clientId ) => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		const blocks = data.map( ( { type, ...block } ) =>
			createBlock( blockNames[ type ], block )
		);

		replaceInnerBlocks( clientId, blocks, false );
	}, [ data, clientId, replaceInnerBlocks ] );
};

export default useBlocksCreator;
