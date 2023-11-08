/**
 * WordPress dependencies
 */
import { select, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

///**
//* Internal dependencies
//*/
//import { createLessonBlock, findLessonBlock } from '../data';
const createLessonBlock = ( item ) => item;
const findLessonBlock = ( lessonBlocks, item ) => ( {
	lessonBlocks,
	item,
} );
//import { useNextLessonIndex } from './next-lesson-index';
const useNextLessonIndex = ( clientId ) => clientId;

const API_PATH = '/sensei-internal/v1/lesson-options';

/**
 * Add existing lessons to the course outline block.
 *
 * @param {string} clientId The quiz block client id.
 * @return {Function} Function that takes an array of lesson IDs and returns a Promise.
 */
export const useAddExistingLessons = ( clientId ) => {
	const lessonBlocks = select( 'core/block-editor' ).getBlocks( clientId );
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const nextInsertIndex = useNextLessonIndex( clientId );

	return ( lessonIds ) => {
		const newLessonIds = lessonIds.filter( ( lessonId ) => {
			return (
				lessonBlocks.length === 0 ||
				! findLessonBlock( lessonBlocks, { id: lessonId } )
			);
		} );

		if ( newLessonIds.length === 0 ) {
			return Promise.resolve( {} );
		}

		// Put this before the auto-block.
		let insertIndex = nextInsertIndex;

		return apiFetch( {
			path: API_PATH + '?lesson_ids=' + newLessonIds.join( ',' ),
			method: 'GET',
		} ).then( ( res ) => {
			if ( Array.isArray( res ) && res.length > 0 ) {
				res.forEach( ( item ) => {
					insertBlock(
						createLessonBlock( item ),
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
