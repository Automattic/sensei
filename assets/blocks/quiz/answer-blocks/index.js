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
 * @property {Function} validate    Validation callback.
 * @property {Object}   messages    Message string.s
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
		view: MultipleChoiceAnswer.view,
		settings: [
			QuestionGradeSettings,
			QuestionMultipleChoiceSettings,
			QuestionAnswerFeedbackSettings,
		],
		validate: ( { answers = [] } = {} ) => {
			return {
				noAnswers: answers.filter( ( a ) => a.label ).length < 2,
				noRightAnswer: ! answers.some( ( a ) => a.correct ),
				noWrongAnswer: ! answers.some( ( a ) => ! a.correct ),
			};
		},
		messages: {
			noAnswers: __(
				'Add at least two answer choice to this question',
				'sensei-lms'
			),
			noRightAnswer: __(
				'Add a right answer to this question',
				'sensei-lms'
			),
			noWrongAnswer: __(
				'Add a wrong answer to this question',
				'sensei-lms'
			),
		},
	},
	boolean: {
		title: __( 'True/False', 'sensei-lms' ),
		description: __(
			'Select whether a statement is true or false.',
			'sensei-lms'
		),
		edit: TrueFalseAnswer,
		view: TrueFalseAnswer.view,
		settings: [ QuestionGradeSettings, QuestionAnswerFeedbackSettings ],
	},
	'gap-fill': {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __( 'Fill in the blank.', 'sensei-lms' ),
		edit: GapFillAnswer,
		view: GapFillAnswer.view,
		settings: [ QuestionGradeSettings, QuestionAnswerFeedbackSettings ],
		validate: ( { before, after, gap } = {} ) => {
			return {
				noBefore: ! before,
				noAfter: ! after,
				noGap: ! gap?.length,
			};
		},
		messages: {
			noBefore: __( 'Add some text before the gap', 'sensei-lms' ),
			noAfter: __( 'Add some text after the gap', 'sensei-lms' ),
			noGap: __( 'Add a right answer to this question', 'sensei-lms' ),
		},
	},
	'single-line': {
		title: __( 'Single Line', 'sensei-lms' ),
		description: __(
			'Short answer to an open-ended question.',
			'sensei-lms'
		),
		edit: SingleLineAnswer,
		view: SingleLineAnswer,
		settings: [ QuestionGradeSettings, QuestionGradingNotesSettings ],
	},
	'multi-line': {
		title: __( 'Multi Line', 'sensei-lms' ),
		description: __(
			'Long answer to an open-ended question.',
			'sensei-lms'
		),
		edit: MultiLineAnswer,
		view: MultiLineAnswer,
		settings: [ QuestionGradeSettings, QuestionGradingNotesSettings ],
	},
	'file-upload': {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Upload a file or document.', 'sensei-lms' ),
		edit: FileUploadAnswer,
		view: FileUploadAnswer,
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
