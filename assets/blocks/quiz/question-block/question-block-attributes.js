/**
 * Map question data to block attributes.
 *
 * @param {Object} question Question data.
 */
/**
 * Internal dependencies
 */
import { normalizeAttributes } from '../data';

/**
 * External dependencies
 */
import { camelCase, snakeCase } from 'lodash';

/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';

export function createQuestionBlockAttributes( question ) {
	const {
		grade,
		type,
		title,
		id,
		categories,
		shared,
		answer_feedback: answerFeedback,
		teacher_notes: gradingNotes,
	} = question;
	const typeAttributes = getTypeAttributes( question );
	return {
		id,
		title,
		type,
		shared,
		...typeAttributes,
		options: {
			grade,
			categories,
			answerFeedback,
			gradingNotes,
			...typeAttributes?.options,
		},
	};
}

/**
 * Type-specific block attributes.
 *
 * @param {Object} apiAttributes The REST API response for a question.
 */
const getTypeAttributes = ( apiAttributes ) => {
	let blockAttributes;

	switch ( apiAttributes.type ) {
		case 'gap-fill':
			blockAttributes = {
				answer: {
					textBefore: apiAttributes.before,
					textAfter: apiAttributes.after,
					rightAnswers: apiAttributes.gap,
				},
			};
			break;
		case 'multiple-choice':
			blockAttributes = {
				answer: {
					answers: apiAttributes.options.map(
						( { label: title, correct: isRight } ) => ( {
							title,
							isRight,
						} )
					),
				},
				options: {
					randomOrder: apiAttributes.random_order,
				},
			};
			break;
		case 'boolean':
			blockAttributes = {
				answer: {
					rightAnswer: apiAttributes.answer,
				},
			};
			break;
		case 'single-line':
		case 'multi-line':
		case 'file-upload':
			blockAttributes = {};
			break;
		default:
			blockAttributes = normalizeAttributes( apiAttributes, camelCase );
			break;
	}

	return applyFilters( 'sensei_quiz_mapped_api_attributes', blockAttributes );
};

/**
 * Generate the REST API arguments from block attributes.
 *
 * @param {Object} attributes Block attributes.
 *
 * @return {Object} REST API parameters.
 */
export const getApiArgsFromAttributes = ( attributes ) => {
	const commonArgs = {
		id: attributes?.id,
		title: attributes?.title,
		type: attributes.type,
		grade: attributes.options?.grade,
	};

	return {
		...commonArgs,
		...getTypeArgs( attributes ),
	};
};

/**
 * Helper method to get type specific REST arguments.
 *
 * @param {Object} attributes Block attributes.
 *
 * @return {Object} Type specific arguments.
 */
const getTypeArgs = ( attributes ) => {
	let apiAttributes;

	switch ( attributes.type ) {
		case 'multiple-choice':
			apiAttributes = {
				answer_feedback: attributes.options?.answerFeedback || null,
				random_order: attributes.options?.randomOrder,
				options: attributes.answer?.answers.map(
					( { title, isRight } ) => ( {
						label: title,
						correct: isRight,
					} )
				),
			};
			break;
		case 'boolean':
			apiAttributes = {
				answer: attributes.answer?.rightAnswer,
				answer_feedback: attributes.options?.answerFeedback || null,
			};
			break;
		case 'gap-fill':
			apiAttributes = {
				before: attributes.answer?.textBefore || '',
				gap: attributes.answer?.rightAnswers || [],
				after: attributes.answer?.textAfter || '',
			};
			break;
		case 'single-line':
		case 'multi-line':
		case 'file-upload':
			apiAttributes = {
				teacher_notes: attributes.options?.gradingNotes || null,
			};
			break;
		default:
			apiAttributes = normalizeAttributes( attributes, snakeCase );
			break;
	}

	return applyFilters( 'sensei_quiz_mapped_block_attributes', apiAttributes );
};
