import { dispatch, select, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { registerStructureStore } from '../../shared/structure/structure-store';
import {
	parseQuestionBlocks,
	syncQuestionBlocks,
	normalizeQuizOptionsAttribute,
} from './data';

export const QUIZ_STORE = 'sensei/quiz-structure';

/**
 * Syncronize this block with quiz data.
 *
 * @param {Object} props
 * @param {string} props.clientId Block ID.
 */
export function useQuizStructure( { clientId } ) {
	const { setBlock, loadStructure } = useDispatch( QUIZ_STORE );
	useEffect( () => {
		setBlock( clientId );
		loadStructure();
	}, [ setBlock, loadStructure, clientId ] );
}

registerStructureStore( {
	storeName: QUIZ_STORE,
	*getEndpoint() {
		const lessonId = yield select( 'core/editor' ).getCurrentPostId();
		return `lesson-quiz/${ lessonId }?context=edit`;
	},

	/**
	 * Update Quiz block with settings and questions.
	 *
	 * @param {Object} structure Quiz structure.
	 */
	*updateBlock( structure ) {
		const clientId = yield select( QUIZ_STORE ).getBlock();

		if ( ! clientId || ! structure ) {
			return;
		}

		const block = yield select( 'core/block-editor' ).getBlock( clientId );

		if ( ! block ) {
			return;
		}

		yield dispatch( 'core/block-editor' ).updateBlockAttributes( clientId, {
			options: normalizeQuizOptionsAttribute( structure.options ),
		} );

		const questionBlocks = yield select( 'core/block-editor' ).getBlocks(
			clientId
		);
		yield dispatch( 'core/block-editor' ).replaceInnerBlocks(
			clientId,
			syncQuestionBlocks( structure.questions, questionBlocks ),
			false
		);
	},

	/**
	 * Parse question blocks and quiz settings from Quiz block.
	 *
	 * @throws {Object} Quiz structure.
	 */
	readBlock() {
		const clientId = select( QUIZ_STORE ).getBlock();
		if ( ! clientId ) return;
		const quizBlock = select( 'core/block-editor' ).getBlock( clientId );
		if ( ! quizBlock ) return;
		const questionBlocks = select( 'core/block-editor' ).getBlocks(
			clientId
		);
		throw {
			code: 'not-implemented',
			options: quizBlock.attributes.options,
			questions: parseQuestionBlocks( questionBlocks ),
		};
	},

	*fetchError( error ) {
		const errorMessage = sprintf(
			/* translators: Error message. */
			__(
				'Quiz settings and questions could not be loaded. %s',
				'sensei-lms'
			),
			error.message
		);
		yield dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
			id: 'quiz-structure-save-error',
		} );
	},

	/**
	 * Display save error notice.
	 *
	 * @param {Object} error
	 */
	*saveError( error ) {
		const errorMessage = sprintf(
			/* translators: Error message. */
			__(
				'Quiz settings and questions could not be updated. %s',
				'sensei-lms'
			),
			error.message
		);
		yield dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
			id: 'quiz-structure-save-error',
		} );
	},

	/**
	 * Clear error notices.
	 */
	clearError() {
		dispatch( 'core/notices' ).removeNotice( 'quiz-structure-save-error' );
	},
} );
