/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';

/**
 * Monitor for questions and disable the lesson quiz when none have been added.
 *
 * @param {string} clientId The quiz block client id.
 */
export const useHasQuestions = ( clientId ) => {
	const questionBlocks = select( 'core/block-editor' )
		.getBlocks( clientId )
		.filter( ( block ) => !! block.attributes.title );

	// Monitor for valid questions.
	useEffect( () => {
		const { _quiz_has_questions: quizHasQuestions } =
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};

		if ( questionBlocks.length ) {
			dispatch( 'core/editor' ).editPost( {
				meta: { _quiz_has_questions: 1 },
			} );
		} else if ( quizHasQuestions ) {
			dispatch( 'core/editor' ).editPost( {
				meta: { _quiz_has_questions: null },
			} );
		}
	}, [ questionBlocks ] );

	// Monitor for quiz block removal.
	useEffect( () => {
		return () => {
			dispatch( 'core/editor' ).editPost( {
				meta: { _quiz_has_questions: null },
			} );
		};
	}, [] );
};
