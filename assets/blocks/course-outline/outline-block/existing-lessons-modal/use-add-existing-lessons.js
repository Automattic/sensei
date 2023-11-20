/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { select, useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lesson type difinition.
 *
 * @typedef {Object} Lesson
 * @property {number} id    Lesson ID.
 * @property {string} title Lesson title
 */

/**
 * Find a question block based on question ID, or title if ID is missing.
 *
 * @param {Array}  blocks
 * @param {Lesson} item
 */
export const findLessonBlock = ( blocks, { id, title } ) => {
	const compare = ( { attributes } ) =>
		id === attributes.id ||
		( ! attributes.id && attributes.title && attributes.title === title );

	return blocks.find( compare );
};

const API_PATH = '/sensei-internal/v1/lessons';

/**
 * Add existing lessons to the course outline block.
 *
 * @param {string} clientId The quiz block client id.
 * @return {Function} Function that takes an array of lesson IDs and returns a Promise.
 */
export const useAddExistingLessons = ( clientId ) => {
	const lessonBlocks = select( 'core/block-editor' ).getBlocks( clientId );
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const nextInsertIndex = useSelect(
		() => select( 'core/block-editor' ).getBlockCount( clientId ),
		[]
	);

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
			path: API_PATH,
			method: 'POST',
			data: {
				lesson_ids: newLessonIds.join( ',' ),
			},
		} ).then( ( res ) => {
			if ( Array.isArray( res ) && res.length > 0 ) {
				res.forEach( ( item ) => {
					insertBlock(
						createBlock( 'sensei-lms/course-outline-lesson', {
							title: item.title,
							type: 'lesson',
							id: item.id,
						} ),
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
