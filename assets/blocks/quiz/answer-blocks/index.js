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
	QuestionGradingNotesSettings,
	QuestionMultipleChoiceSettings,
} from '../question-block/settings';

/**
 * @typedef QuestionType
 *
 * @property {string}   title       Question type name.
 * @property {string}   description Question type description.
 * @property {Function} edit        Editor component.
 * @property {boolean}  feedback    Question type can have answer feedback.
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
		settings: [ QuestionMultipleChoiceSettings ],
		feedback: true,
		validate: ( { answers = [] } = {} ) => {
			return {
				noAnswers: answers.filter( ( a ) => a.label ).length < 2,
				noRightAnswer: ! answers.some( ( a ) => a.correct && a.label ),
				noRightAnswerWhitespace: ! answers.some(
					( a ) => a.correct && a.label.trim()
				),
				noWrongAnswer: ! answers.some(
					( a ) => ! a.correct && a.label
				),
				noWrongAnswerWhitespace: ! answers.some(
					( a ) => ! a.correct && a.label.trim()
				),
			};
		},
		messages: {
			noAnswers: __(
				'Add at least one right and one wrong answer.',
				'sensei-lms'
			),
			noRightAnswer: __(
				'Add a right answer to this question.',
				'sensei-lms'
			),
			noRightAnswerWhitespace: __(
				'The value of the right answer can not be blank space.',
				'sensei-lms'
			),
			noWrongAnswer: __(
				'Add a wrong answer to this question. Value can not be blank space.',
				'sensei-lms'
			),
			noWrongAnswerWhitespace: __(
				'The value of the wrong answer can not be blank space.',
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
		feedback: true,
		settings: [],
	},
	'gap-fill': {
		title: __( 'Gap Fill', 'sensei-lms' ),
		description: __( 'Fill in the blank.', 'sensei-lms' ),
		edit: GapFillAnswer,
		view: GapFillAnswer.view,
		feedback: true,
		settings: [],
		validate: ( { before, after, gap } = {} ) => {
			return {
				noGap: ! gap?.filter( ( val ) => val !== '' ).length,
				noGapWhitespace: ! gap?.filter( ( val ) => val.trim() !== '' )
					.length,
				noBeforeAndNoAfter: ! before && ! after,
				noBeforeAndNoAfterWhitespace:
					! before?.trim() && ! after?.trim(),
			};
		},
		messages: {
			noGap: __( 'Add a right answer to this question.', 'sensei-lms' ),
			noGapWhitespace: __(
				'The value of a right answer can not be blank space.',
				'sensei-lms'
			),
			noBeforeAndNoAfter: __(
				'Add text before or after the gap. Value can not be blank space.',
				'sensei-lms'
			),
			noBeforeAndNoAfterWhitespace: __(
				'Value of the text before or after the gap can not be blank space.',
				'sensei-lms'
			),
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
		settings: [ QuestionGradingNotesSettings ],
	},
	'multi-line': {
		title: __( 'Multi Line', 'sensei-lms' ),
		description: __(
			'Long answer to an open-ended question.',
			'sensei-lms'
		),
		edit: MultiLineAnswer,
		view: MultiLineAnswer,
		settings: [ QuestionGradingNotesSettings ],
	},
	'file-upload': {
		title: __( 'File Upload', 'sensei-lms' ),
		description: __( 'Upload a file or document.', 'sensei-lms' ),
		edit: FileUploadAnswer,
		view: FileUploadAnswer,
		settings: [ QuestionGradingNotesSettings ],
	},
};

// Commonly used core settings for use in custom question types.
const availableCoreSettings = {
	QuestionGradingNotesSettings,
};

/**
 * Question types before the filter being applied.
 */
export const unfilteredQuestionTypes = questionTypes;

/**
 * Filters the quiz editor question types in order to support custom ones.
 *
 * @param {Object}   questionTypes             The question types.
 * @param {string}   questionTypes.title       The title of the question.
 * @param {string}   questionTypes.description The description of the question.
 * @param {Function} questionTypes.edit        The block edit function for the question. Attributes under
 *                                             'answer', will be passed to this component.
 * @param {Array}    questionTypes.settings    An array of settings components to use in the sidebar.
 *                                             Attributes under 'options', will be passed to all settings
 *                                             components.
 * @param {Object}   availableCoreSettings     Core settings that can be included in custom question types.
 */
export default applyFilters(
	'sensei-lms.Question.questionTypes',
	questionTypes,
	availableCoreSettings
);
