/**
 * WordPress dependencies
 */
import { select, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { createQuestionBlock, findQuestionBlock } from '../data';
import apiFetch from '@wordpress/api-fetch';

const API_PATH = '/sensei-internal/v1/question-options';

/**
 * Add existing questions to the quiz block.
 *
 * @param {string} clientId The quiz block client id.
 */
export const useAddExistingQuestions = ( clientId ) => {
	const questionBlocks = select( 'core/block-editor' ).getBlocks( clientId );
	const { insertBlock } = useDispatch( 'core/block-editor' );

	return ( questionIds, completeCallback ) => {
		const newQuestionIds = questionIds.filter( ( questionId ) => {
			return (
				questionBlocks.length === 0 ||
				! findQuestionBlock( questionBlocks, { id: questionId } )
			);
		} );

		if ( newQuestionIds.length === 0 ) {
			completeCallback();

			return;
		}

		apiFetch( {
			path: API_PATH + '?question_ids=' + newQuestionIds.join( ',' ),
			method: 'GET',
		} ).then( ( res ) => {
			if ( Array.isArray( res ) && res.length > 0 ) {
				res.forEach( ( item ) => {
					insertBlock(
						createQuestionBlock( item ),
						undefined,
						clientId,
						false
					);
				} );

				completeCallback();
			}
		} );
	};
};
