/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import FileUploadAnswer from './file-upload';
import GapFillAnswer from './gap-fill';
import MultiLineAnswer from './multi-line';
import MultipleChoiceAnswer from './multiple-choice';
import SingleLineAnswer from './single-line';
import TrueFalseAnswer from './true-false';
import {
	QuestionAnswerFeedbackSettings,
	QuestionGradeSettings,
	QuestionGradingNotesSettings,
	QuestionMultipleChoiceSettings,
} from '../question-block/settings';

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
		settings: [
			QuestionGradeSettings,
			QuestionMultipleChoiceSettings,
			QuestionAnswerFeedbackSettings,
		],
	},
	boolean: {
		title: __( 'True/False', 'sensei-lms' ),
		description: __(
			'Select whether a statement is true or false.',
			'sensei-lms'
		),
		edit: TrueFalseAnswer,
		settings: [ QuestionGradeSettings, QuestionAnswerFeedbackSettings ],
	},
	'gap-fill': {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __( 'Fill in the blank.', 'sensei-lms' ),
		edit: GapFillAnswer,
		settings: [ QuestionGradeSettings, QuestionAnswerFeedbackSettings ],
	},
	'single-line': {
		title: __( 'Single Line', 'sensei-lms' ),
		description: __(
			'Short answer to an open-ended question.',
			'sensei-lms'
		),
		edit: SingleLineAnswer,
		settings: [ QuestionGradeSettings, QuestionGradingNotesSettings ],
	},
	'multi-line': {
		title: __( 'Multi Line', 'sensei-lms' ),
		description: __(
			'Long answer to an open-ended question.',
			'sensei-lms'
		),
		edit: MultiLineAnswer,
		settings: [ QuestionGradeSettings, QuestionGradingNotesSettings ],
	},
	'file-upload': {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Upload a file or document.', 'sensei-lms' ),
		edit: FileUploadAnswer,
		settings: [ QuestionGradeSettings, QuestionGradingNotesSettings ],
	},
};

/**
 * Filters the quiz editor question types in order to support custom ones.
 *
 * @see sensei_quiz_mapped_api_attributes
 * @see sensei_quiz_mapped_block_attributes
 *
 * @param {Object}   questionTypes             The question types.
 * @param {string}   questionTypes.title       The title of the question.
 * @param {string}   questionTypes.description The description of the question.
 * @param {Function} questionTypes.edit        The block edit function for the question. Attributes under 'answer', as
 *                                             returned from the 'sensei_quiz_mapped_api_attributes' filter, will be
 *                                             passed to this component.
 * @param {Array}    questionTypes.settings    An array of settings components to use in the sidebar. Attributes under 'options', as
 *                                             returned from the 'sensei_quiz_mapped_api_attributes' filter, will be
 *                                             passed to all settings components.
 */
export default applyFilters( 'sensei_quiz_question_types', questionTypes );
