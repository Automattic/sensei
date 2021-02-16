import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import FileUploadAnswer from './file-upload';
import GapFillAnswer from './gap-fill';
import MultiLineAnswer from './multi-line';
import MultipleChoiceAnswer from './multiple-choice';
import SingleLineAnswer from './single-line';
import TrueFalseAnswer from './true-false';

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
	'multiple-choice': {
		title: __( 'Multiple Choice', 'sensei-lms' ),
		description: __( 'Select from a list of options.', 'sensei-lms' ),
		edit: MultipleChoiceAnswer,
	},
	boolean: {
		title: __( 'True / False', 'sensei-lms' ),
		description: __(
			'Select whether a statement is true or false.',
			'sensei-lms'
		),
		edit: TrueFalseAnswer,
	},
	'gap-fill': {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __( 'Fill in the blank.', 'sensei-lms' ),
		edit: GapFillAnswer,
	},
	'single-line': {
		title: __( 'Single-line', 'sensei-lms' ),
		description: __(
			'Short answer to an open-ended question.',
			'sensei-lms'
		),
		edit: SingleLineAnswer,
	},
	'multi-line': {
		title: __( 'Multi-line', 'sensei-lms' ),
		description: __(
			'Long answer to an open-ended question.',
			'sensei-lms'
		),
		edit: MultiLineAnswer,
	},
	'file-upload': {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Upload a file or document.', 'sensei-lms' ),
		edit: FileUploadAnswer,
	},
};

/**
 * Quiz editor question types.
 *
 * @param {Object.<string, QuestionType>}
 */
export default applyFilters( 'sensei_quiz_question_types', questionTypes );
