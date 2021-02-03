import { createBlock, getBlockContent, rawHandler } from '@wordpress/blocks';
import { name as questionBlockName } from './question-block';

/**
 * Quiz settings and questions data.
 *
 * @typedef {Object} QuizStructure
 * @property {Object}         settings  Quiz settings.
 * @property {QuizQuestion[]} questions Questions.
 */

/**
 * Quiz question data.
 *
 * @typedef {Object} QuizQuestion
 * @property {number} id          Question ID.
 * @property {string} type        Question type
 * @property {string} title       Question title
 * @property {string} description Question description blocks
 * @property {Object} answers     Question answer settings
 * @property {Object} settings    Question settings
 */

/**
 * Create blocks based on the structure, keeping existing block attributes.
 *
 * Matches blocks based on question ID.
 *
 * @param {QuizQuestion[]} structure Quiz questions.
 * @param {Object[]}       blocks    Existing blocks.
 * @return {Object[]} Updated blocks.
 */
export function syncQuestionBlocks( structure, blocks ) {
	return ( structure || [] ).map( ( item ) => {
		const { description, ...attributes } = item;

		let block = findQuestionBlock( blocks, item );

		const innerBlocks =
			( description && rawHandler( { HTML: description } ) ) ||
			block.innerBlocks;

		if ( ! block ) {
			block = createBlock( questionBlockName, attributes, innerBlocks );
		} else {
			block.attributes = { ...block.attributes, ...attributes };
			block.innerBlocks = innerBlocks;
		}

		return block;
	} );
}

/**
 * Convert blocks to question structure.
 *
 * @param {Object[]} blocks Blocks.
 * @return {QuizQuestion[]} Question structure
 */
export function parseQuestionBlocks( blocks ) {
	return blocks
		?.map( ( block ) => {
			return {
				...block.attributes,
				description: getBlockContent( block ),
			};
		} )
		.filter( ( block ) => !! block.title );
}

/**
 * Find a question block based on question ID, or title if ID is missing.
 *
 * @param {Array}        blocks
 * @param {QuizQuestion} item
 */
const findQuestionBlock = ( blocks, { id, title } ) => {
	const compare = ( { attributes } ) =>
		id === attributes.id ||
		( ! attributes.id && attributes.title === title );
	return blocks.find( compare );
};
