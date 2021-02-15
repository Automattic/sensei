import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import {
	QuestionAnswerFeedbackSettings,
	QuestionGradingNotesSettings,
} from '../question-block/settings';
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
	multichoice: {
		title: __( 'Multiple Choice', 'sensei-lms' ),
		description: __( 'Select from a list of options.', 'sensei-lms' ),
		edit: MultipleChoiceAnswer,
		settings: [ QuestionAnswerFeedbackSettings ],
	},
	truefalse: {
		title: __( 'True / False', 'sensei-lms' ),
		description: __(
			'Select whether a statement is true or false.',
			'sensei-lms'
		),
		edit: TrueFalseAnswer,
		settings: [ QuestionAnswerFeedbackSettings ],
	},
	gap: {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __( 'Fill in the blank.', 'sensei-lms' ),
		edit: GapFillAnswer,
		settings: [ QuestionAnswerFeedbackSettings ],
	},
	'single-line': {
		title: __( 'Single-line', 'sensei-lms' ),
		description: __(
			'Short answer to an open-ended question.',
			'sensei-lms'
		),
		edit: SingleLineAnswer,
		settings: [ QuestionGradingNotesSettings ],
	},
	'multi-line': {
		title: __( 'Multi-line', 'sensei-lms' ),
		description: __(
			'Long answer to an open-ended question.',
			'sensei-lms'
		),
		edit: MultiLineAnswer,
		settings: [ QuestionGradingNotesSettings ],
	},
	'file-upload': {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Upload a file or document.', 'sensei-lms' ),
		edit: FileUploadAnswer,
		settings: [ QuestionGradingNotesSettings ],
	},
};

/**
 * Quiz editor question types.
 *
 * @param {Object.<string, QuestionType>}
 */
export default applyFilters( 'sensei_quiz_question_types', questionTypes );
