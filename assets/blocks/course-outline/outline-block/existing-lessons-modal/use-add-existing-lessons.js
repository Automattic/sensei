/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { select, useDispatch, useSelect } from '@wordpress/data';

/**
 * Lesson type definition.
 *
 * @typedef {Object} Lesson
 * @property {number} id    Lesson ID.
 * @property {string} title Lesson title
 */

/**
 * Find a lesson block based on lesson ID, or title if ID is missing.
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

/**
 * Add existing lessons to the course outline block.
 *
 * @param {string} clientId The quiz block client id.
 * @return {Function} Function that takes an array of lesson IDs and returns a Promise.
 */
export const useAddExistingLessons = ( clientId ) => {
	const lessonBlocks = useSelect(
		() => select( 'core/block-editor' ).getBlocks( clientId ),
		[]
	);
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const nextInsertIndex = useSelect(
		() => select( 'core/block-editor' ).getBlockCount( clientId ),
		[]
	);

	return ( lessons ) => {
		const newLessons = lessons.filter( ( lesson ) => {
			return (
				lessonBlocks.length === 0 ||
				! findLessonBlock( lessonBlocks, { id: lesson.id } )
			);
		} );

		if ( newLessons.length === 0 ) {
			return Promise.resolve( {} );
		}

		// Put this before the auto-block.
		let insertIndex = nextInsertIndex;
		lessons.forEach( ( item ) => {
			insertBlock(
				createBlock( 'sensei-lms/course-outline-lesson', {
					id: item.id,
					type: 'lesson',
					title: item.title.raw,
					draft: item.post_status === 'draft',
				} ),
				insertIndex,
				clientId,
				false
			);

			insertIndex++;
		} );

		return Promise.resolve( {} );
	};
};
