/**
 * WordPress dependencies
 */
import { select, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	createQuestionBlock,
	findQuestionBlock,
	isQuestionEmpty,
} from '../data';

const API_PATH = '/sensei-internal/v1/question-options';

/**
 * Add existing questions to the quiz block.
 *
 * @param {string} clientId The quiz block client id.
 * @return {Function} Function that takes an array of question IDs and returns a Promise.
 */
export const useAddExistingQuestions = ( clientId ) => {
	const questionBlocks = select( 'core/block-editor' ).getBlocks( clientId );
	const { insertBlock } = useDispatch( 'core/block-editor' );

	return ( questionIds ) => {
		const newQuestionIds = questionIds.filter( ( questionId ) => {
			return (
				questionBlocks.length === 0 ||
				! findQuestionBlock( questionBlocks, { id: questionId } )
			);
		} );

		if ( newQuestionIds.length === 0 ) {
			return Promise.resolve( {} );
		}

		// Put this before the auto-block.
		const lastBlock =
			questionBlocks.length &&
			questionBlocks[ questionBlocks.length - 1 ];
		const hasEmptyLastBlock =
			lastBlock && isQuestionEmpty( lastBlock.attributes );

		let insertIndex = questionBlocks.length;

		if ( hasEmptyLastBlock ) {
			insertIndex -= 1;
		}

		return apiFetch( {
			path: API_PATH + '?question_ids=' + newQuestionIds.join( ',' ),
			method: 'GET',
		} ).then( ( res ) => {
			if ( Array.isArray( res ) && res.length > 0 ) {
				res.forEach( ( item ) => {
					insertBlock(
						createQuestionBlock( item ),
						insertIndex,
						clientId,
						false
					);

					insertIndex++;
				} );
			}
		} );
	};
};
