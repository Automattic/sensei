/**
 * WordPress dependencies
 */
import { createBlock, getBlockContent, rawHandler } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import questionBlock from './question-block';
import categoryQuestionBlock from './category-question-block';

/**
 * External dependencies
 */
import { mapKeys, mapValues, isObject, omit } from 'lodash';

/**
 * Question attributes that are derived from the server and must not be sent
 * back as editable structure.
 *
 * @type {string[]}
 */
export const READ_ONLY_ATTRIBUTES = [
	'categories',
	'shared',
	'options.studentHelp',
	'media',
	'categoryName',
	'lock',
];

/**
 * Normalize a quiz REST response into the structure the editor tracks.
 *
 * Only the fields that make up the editor's structure are kept. Other plugins
 * (e.g. Polylang) may inject extra top-level fields such as `lang` and
 * `translations` into the REST response; including them here would make the
 * structure comparison detect a permanent diff and trigger an infinite save
 * loop.
 *
 * @param {Object} structure The quiz response.
 *
 * @return {Object} The normalized structure.
 */
export function normalizeServerStructure( structure ) {
	if ( ! structure ) {
		return {};
	}

	return {
		lesson_status: structure.lesson_status,
		lesson_title: structure.lesson_title,
		options: structure.options,
		questions: ( structure.questions || [] ).map( ( question ) =>
			omit( question, READ_ONLY_ATTRIBUTES )
		),
	};
}

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
 * @property {Object} options     Question options.
 */

/**
 * Quiz category question data.
 *
 * @typedef {Object} QuizCategoryQuestion
 * @property {number} id               Question ID.
 * @property {string} type             Question type
 * @property {Object} options          Question settings
 * @property {number} options.category Category for question.
 * @property {number} options.number   Number of questions to show from category.
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

		if ( ! block ) {
			block = createQuestionBlock( item );
		} else {
			block.attributes = {
				...block.attributes,
				...attributes,
			};

			const innerBlocks =
				( description && rawHandler( { HTML: description } ) ) || [];

			dispatch( 'core/block-editor' ).replaceInnerBlocks(
				block.clientId,
				innerBlocks
			);
		}

		return block;
	} );
}

/**
 * Manually run our deprecated migrations for the question block.
 *
 * @param {Object} attributes  Block attributes.
 * @param {Array}  innerBlocks Inner blocks.
 * @return {Array} Tuple of attributes and innerBlocks
 */
function prepareQuestionBlock( attributes, innerBlocks ) {
	questionBlock.deprecated.forEach( ( item ) => {
		// Check our flag for deprecations that should run here.
		if (
			item.onProgrammaticCreation &&
			item.isEligible( attributes, innerBlocks )
		) {
			[ attributes, innerBlocks ] = item.migrate(
				attributes,
				innerBlocks
			);
		}
	} );

	return [ attributes, innerBlocks ];
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
		const attributes = omit( block.attributes, [ 'className' ] );
		if ( block.attributes.type === 'category-question' ) {
			return attributes;
		}

		return {
			...attributes,
			description: getBlockContent( block ),
		};
	} );

	if ( 0 === questions.length ) {
		return questions;
	}

	const lastQuestion = questions.pop();

	if ( ! isQuestionEmpty( lastQuestion ) ) {
		questions.push( lastQuestion );
	}

	return questions;
}

/**
 * Create a new question block.
 *
 * @param {Object} question Question item.
 *
 * @return {QuizQuestion} Block.
 */
export function createQuestionBlock( question ) {
	if ( question.type === 'category-question' ) {
		return createBlock( categoryQuestionBlock.name, question, [] );
	}

	const [ attributes, innerBlocks ] = prepareQuestionBlock(
		question,
		( question.description &&
			rawHandler( { HTML: question.description } ) ) ||
			[]
	);

	return createBlock( questionBlock.name, attributes, innerBlocks );
}

/**
 * Find a question block based on question ID, or title if ID is missing.
 *
 * @param {Array}                             blocks
 * @param {QuizQuestion|QuizCategoryQuestion} item
 */
export const findQuestionBlock = ( blocks, { id, title, options } ) => {
	const category = options?.category;

	const compare = ( { attributes } ) =>
		id === attributes.id ||
		( ! attributes.id && attributes.title && attributes.title === title ) ||
		( ! attributes.id &&
			attributes.options?.category &&
			attributes.options?.category === category );
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

/**
 * Checks whether a block is empty.
 *
 * @param {Array} attributes Question attributes.
 *
 * @return {boolean} If the question is empty.
 */
export const isQuestionEmpty = ( attributes ) => {
	if ( attributes.type === 'category-question' ) {
		return ! attributes.options.category;
	}

	return ! attributes.title;
};
