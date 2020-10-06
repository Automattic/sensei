import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Insert an empty lesson block to the end of the module when it's selected.
 *
 * @param {Object} props Block properties.
 */
export const useInsertLessonBlock = ( props ) => {
	const { clientId, isSelected } = props;
	const { insertBlock, removeBlock } = useDispatch( 'core/block-editor' );

	const addBlock = useCallback(
		( name ) =>
			insertBlock( createBlock( name ), undefined, clientId, false ),
		[ insertBlock, clientId ]
	);

	const lessonBlocks = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	);

	const hasSelected =
		useSelect( ( select ) =>
			select( 'core/block-editor' ).hasSelectedInnerBlock( clientId )
		) || isSelected;

	useEffect( () => {
		const lastLessonBlock =
			lessonBlocks.length && lessonBlocks[ lessonBlocks.length - 1 ];
		const hasEmptyLastLessonBlock =
			lastLessonBlock && ! lastLessonBlock.attributes.title;

		if ( hasSelected && ! hasEmptyLastLessonBlock ) {
			addBlock( 'sensei-lms/course-outline-lesson' );
		}
		if (
			! hasSelected &&
			hasEmptyLastLessonBlock &&
			1 !== lessonBlocks.length
		) {
			removeBlock( lastLessonBlock.clientId, false );
		}
	}, [ lessonBlocks, hasSelected, addBlock, removeBlock ] );
};
