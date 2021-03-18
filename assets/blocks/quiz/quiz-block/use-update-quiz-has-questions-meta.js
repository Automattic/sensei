/**
 * WordPress dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isQuestionEmpty } from '../data';

/**
 * Monitor for questions and disable the lesson quiz when none have been added.
 *
 * @param {string} clientId The quiz block client id.
 */
export const useUpdateQuizHasQuestionsMeta = ( clientId ) => {
	const META_KEY = '_quiz_has_questions';
	const questionBlocks = useSelect( ( select ) =>
		select( 'core/block-editor' )
			.getBlocks( clientId )
			.filter( ( block ) => ! isQuestionEmpty( block.attributes ) )
	);

	const { editedValue: quizHasQuestionsMeta, currentValue } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			return {
				editedValue: editor.getEditedPostAttribute( 'meta' )[
					META_KEY
				],
				currentValue: editor.getCurrentPostAttribute( 'meta' )[
					META_KEY
				],
			};
		}
	);

	const { editPost } = useDispatch( 'core/editor' );
	const setQuizHasQuestionsMeta = useCallback(
		( enable ) => {
			// Don't send an update to null if the meta is already unset.
			const disabledValue = currentValue ? null : undefined;

			return editPost( {
				meta: { [ META_KEY ]: enable ? 1 : disabledValue },
			} );
		},
		[ editPost, currentValue ]
	);

	// Monitor for valid questions.
	useEffect( () => {
		if ( ! quizHasQuestionsMeta && questionBlocks.length ) {
			setQuizHasQuestionsMeta( true );
		}
		if ( quizHasQuestionsMeta && ! questionBlocks.length ) {
			setQuizHasQuestionsMeta( false );
		}
	}, [
		questionBlocks.length,
		quizHasQuestionsMeta,
		setQuizHasQuestionsMeta,
	] );

	// Monitor for quiz block removal.
	useEffect( () => {
		return () => {
			setQuizHasQuestionsMeta( false );
		};
	}, [ setQuizHasQuestionsMeta ] );
};
