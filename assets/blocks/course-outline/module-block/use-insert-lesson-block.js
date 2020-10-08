import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Insert an empty lesson block to the end of the module when it's selected.
 *
 * @param {Object} props Block properties.
 */
export const useInsertLessonBlock = ( props ) => {
	const [
		implicitLessonBlockClientId,
		setImplicitLessonBlockClientId,
	] = useState( null );
	const { clientId, isSelected } = props;
	const { insertBlock, removeBlock } = useDispatch( 'core/block-editor' );

	const addBlock = useCallback(
		( name ) => {
			const block = createBlock( name );
			insertBlock( block, undefined, clientId, false );
			setImplicitLessonBlockClientId( block.clientId );
		},
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
		if ( ! hasSelected ) setImplicitLessonBlockClientId( null );
	}, [ hasSelected ] );
	useEffect( () => {
		const lastLessonBlock =
			lessonBlocks.length && lessonBlocks[ lessonBlocks.length - 1 ];
		const hasEmptyLastLessonBlock =
			lastLessonBlock && ! lastLessonBlock.attributes.title;

		if (
			hasSelected &&
			! hasEmptyLastLessonBlock &&
			! implicitLessonBlockClientId
		) {
			addBlock( 'sensei-lms/course-outline-lesson' );
		}
		if (
			! hasSelected &&
			hasEmptyLastLessonBlock &&
			lastLessonBlock.clientId === implicitLessonBlockClientId &&
			1 !== lessonBlocks.length
		) {
			removeBlock( lastLessonBlock.clientId, false );
			setImplicitLessonBlockClientId( null );
		}
	}, [
		lessonBlocks,
		hasSelected,
		addBlock,
		removeBlock,
		implicitLessonBlockClientId,
	] );
};
