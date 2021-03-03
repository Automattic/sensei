/**
 * WordPress dependencies
 */
import { createBlock, getBlockContent, rawHandler } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import questionBlock from './question-block';

/**
 * External dependencies
 */
import { mapKeys, mapValues, isObject } from 'lodash';

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
	if ( ! structure || structure.length === 0 ) {
		return [ createBlock( 'sensei-lms/quiz-question', {} ) ];
	}

	return ( structure || [] ).map( ( item ) => {
		const { description, ...attributes } = item;

		let block = blocks ? findQuestionBlock( blocks, item ) : null;

		const innerBlocks =
			( description && rawHandler( { HTML: description } ) ) ||
			block?.innerBlocks;

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
 *
 * @return {QuizQuestion[]} Question structure
 */
export function parseQuestionBlocks( blocks ) {
	const questions = blocks?.map( ( block ) => {
		return {
			...block.attributes,
			description: getBlockContent( block ),
		};
	} );

	const lastQuestion = questions.pop();

	if ( lastQuestion.title ) {
		questions.push( lastQuestion );
	}

	return questions;
}

/**
 * Create a new question block.
 *
 * @param {Object} item Question item.
 *
 * @return {QuizQuestion} Block.
 */
export function createQuestionBlock( item ) {
	const { description, ...attributes } = item;

	const innerBlocks =
		( description && rawHandler( { HTML: description } ) ) || [];

	return createBlock( questionBlock.name, attributes, innerBlocks );
}

/**
 * Find a question block based on question ID, or title if ID is missing.
 *
 * @param {Array}        blocks
 * @param {QuizQuestion} item
 */
export const findQuestionBlock = ( blocks, { id, title } ) => {
	const compare = ( { attributes } ) =>
		id === attributes.id ||
		( ! attributes.id && attributes.title && attributes.title === title );
	return blocks.find( compare );
};

/**
 * Normalize an object by applying a mapping function to it's keys, including nested ones.
 *
 * @param {Object}   options     Options object to normalize.
 * @param {Function} mapFunction Function to apply.
 */
export const normalizeAttributes = ( options, mapFunction ) => {
	const normalizedOptions = mapKeys( options, ( value, key ) =>
		mapFunction( key )
	);

	return mapValues( normalizedOptions, ( value ) => {
		if ( isObject( value ) ) {
			return normalizeAttributes( value, mapFunction );
		}

		return value;
	} );
};
