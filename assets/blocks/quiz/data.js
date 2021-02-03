import { getBlockContent } from '@wordpress/blocks';

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
