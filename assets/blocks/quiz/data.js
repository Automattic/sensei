import { createBlock, getBlockContent, rawHandler } from '@wordpress/blocks';
import { createQuestionBlockAttributes } from './question-block/question-block-attributes';
import questionBlock from './question-block';

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
		const { description, ...question } = item;

		let block = blocks ? findQuestionBlock( blocks, item ) : null;

		const innerBlocks =
			( description && rawHandler( { HTML: description } ) ) ||
			block?.innerBlocks;

		const attributes = createQuestionBlockAttributes( question );

		if ( ! block ) {
			block = createBlock( questionBlock.name, attributes, innerBlocks );
		} else {
			block.attributes = {
				...block.attributes,
				...attributes,
			};
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

/**
 * Normalize quiz options attribute coming from REST API.
 *
 * @param {Object}  options                       Quiz options.
 * @param {boolean} options.pass_required         Whether is pass required.
 * @param {number}  options.quiz_passmark         Percentage quiz passmark.
 * @param {boolean} options.auto_grade            Whether auto grade.
 * @param {boolean} options.allow_retakes         Whether allow retakes.
 * @param {boolean} options.ramdom_question_order Whether random question order.
 * @param {number}  options.show_questions        Number of questions to show.
 */
export const normalizeQuizOptionsAttribute = ( options ) => ( {
	passRequired: options.pass_required,
	quizPassmark: options.quiz_passmark,
	autoGrade: options.auto_grade,
	allowRetakes: options.allow_retakes,
	randomQuestionOrder: options.ramdom_question_order,
	showQuestions: options.show_questions,
} );
