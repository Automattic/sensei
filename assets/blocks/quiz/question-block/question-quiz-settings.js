/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Returns a method which opens the quiz settings.
 *
 * @param {string} clientId The question's clientId.
 *
 * @return {Function} A function which opens the quiz settings.
 */
export const useQuizSettings = ( clientId ) => {
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );
	const { selectBlock } = useDispatch( 'core/block-editor' );
	const { parents } = useSelect(
		( select ) => ( {
			parents: select( 'core/block-editor' ).getBlockParentsByBlockName(
				clientId,
				'sensei-lms/quiz'
			),
		} ),
		[]
	);

	let onOpenQuizSettings = () => {};

	if ( parents.length > 0 ) {
		onOpenQuizSettings = () => {
			selectBlock( parents[ 0 ] );
			openGeneralSidebar( 'edit-post/block' );
		};
	}

	return { onOpenQuizSettings };
};
