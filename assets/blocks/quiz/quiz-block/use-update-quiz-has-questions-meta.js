/**
 * WordPress dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, useDispatch, select as dataSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';

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

	// It doesn't use the `useSelect` to get the blocks from the main registry.
	// It avoids getting the blocks from the preview thumbnails.
	const questionBlocks = dataSelect( blockEditorStore )
		.getBlocks( clientId )
		.filter( ( block ) => ! isQuestionEmpty( block.attributes ) );

	const { editedValue: quizHasQuestionsMeta } = useSelect( ( select ) => {
		const editor = select( editorStore );
		return {
			editedValue: editor.getEditedPostAttribute( 'meta' )[ META_KEY ],
		};
	} );

	const { editPost } = useDispatch( editorStore );
	const setQuizHasQuestionsMeta = useCallback(
		( enable ) => {
			return editPost( {
				meta: { [ META_KEY ]: enable ? 1 : 0 },
			} );
		},
		[ editPost ]
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
