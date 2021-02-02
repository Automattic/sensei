import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * @typedef QuestionType
 *
 * @property {string}   title       Question type name.
 * @property {string}   description Question type description.
 * @property {Function} edit        Editor component.
 */

/**
 * Question type definitions.
 *
 * @type {Object.<string, QuestionType>}
 */
const questionTypes = {
	multichoice: {
		title: __( 'Multiple Choice', 'sensei-lms' ),
		description: __(
			'Select one or more answers from a list of choices.',
			'sensei-lms'
		),
		edit: () => <div> [Multiple Choice] </div>,
	},
	truefalse: {
		title: __( 'True / False', 'sensei-lms' ),
		description: __( 'True or false question.', 'sensei-lms' ),
		edit: () => <div> [True/False] </div>,
	},
	gap: {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __(
			'Fill in the missing part of a sentence.',
			'sensei-lms'
		),
		edit: () => <div> [Gap Fill] </div>,
	},
	open: {
		title: __( 'Open-Ended', 'sensei-lms' ),
		description: __( 'Require a written answer.', 'sensei-lms' ),
		edit: () => <div> [Open-Ended] </div>,
	},
	file: {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Require a file to be uploaded.', 'sensei-lms' ),
		edit: () => <div> [File Upload] </div>,
	},
};

/**
 * Quiz editor question types.
 *
 * @param {Object.<string, QuestionType>}
 */
export default applyFilters( 'sensei_quiz_question_types', questionTypes );
