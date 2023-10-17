/**
 * WordPress dependencies
 */
import { select, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

///**
//* Internal dependencies
//*/
//import { createQuestionBlock, findQuestionBlock } from '../data';
const createQuestionBlock = ( item ) => item;
const findQuestionBlock = ( questionBlocks, item ) => ( {
	questionBlocks,
	item,
} );
//import { useNextQuestionIndex } from './next-question-index';
const useNextQuestionIndex = ( clientId ) => clientId;

const API_PATH = '/sensei-internal/v1/question-options';

/**
 * Add existing lessons to the course outline block.
 *
 * @param {string} clientId The quiz block client id.
 * @return {Function} Function that takes an array of lesson IDs and returns a Promise.
 */
export const useAddExistingLessons = ( clientId ) => {
	const questionBlocks = select( 'core/block-editor' ).getBlocks( clientId );
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const nextInsertIndex = useNextQuestionIndex( clientId );

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
		let insertIndex = nextInsertIndex;

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
