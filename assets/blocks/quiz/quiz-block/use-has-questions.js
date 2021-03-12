/**
 * WordPress dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, select as selectData, useDispatch } from '@wordpress/data';

/**
 * Monitor for questions and disable the lesson quiz when none have been added.
 *
 * @param {string} clientId The quiz block client id.
 */
export const useHasQuestions = ( clientId ) => {
	const questionBlocks = useSelect( ( select ) =>
		select( 'core/block-editor' )
			.getBlocks( clientId )
			.filter( ( block ) => !! block.attributes.title )
	);

	const { editPost } = useDispatch( 'core/editor' );
	const setQuizHasQuestions = useCallback(
		( on ) => {
			const { _quiz_has_questions: savedValue } =
				selectData( 'core/editor' ).getCurrentPostAttribute( 'meta' ) ||
				{};
			// Don't send an update to null if the meta is already unset.
			const emptyValue = savedValue ? null : undefined;
			return editPost( {
				meta: { _quiz_has_questions: on ? 1 : emptyValue },
			} );
		},
		[ editPost ]
	);

	// Monitor for valid questions.
	useEffect( () => {
		const { _quiz_has_questions: quizHasQuestions } =
			selectData( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};

		if ( ! quizHasQuestions && questionBlocks.length ) {
			setQuizHasQuestions( true );
		}
		if ( quizHasQuestions && ! questionBlocks.length ) {
			setQuizHasQuestions( false );
		}
	}, [ questionBlocks, setQuizHasQuestions ] );

	// Monitor for quiz block removal.
	useEffect( () => {
		return () => {
			setQuizHasQuestions( false );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );
};
