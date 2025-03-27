/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editPostStore } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { TourStep } from '../types';
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import {
	highlightElementsWithBorders,
	performStepActionsAsync,
	scrollToCenter,
} from '../helper';

export const getQuizBlock = () =>
	getFirstBlockByName(
		'sensei-lms/quiz',
		select( blockEditorStore ).getBlocks()
	);

export const getFirstQuestionBlock = () =>
	getFirstBlockByName(
		'sensei-lms/quiz-question',
		select( blockEditorStore ).getBlocks()
	);

export const getFirstBooleanQuestionBlock = () => {
	const quizBlock = getQuizBlock();
	if ( ! quizBlock ) {
		return null;
	}

	const questionBlocks = select( blockEditorStore ).getBlocks(
		quizBlock.clientId
	);

	const booleanQuestionBlock = questionBlocks.find(
		( block ) => 'boolean' === block.attributes.type
	);

	if ( ! booleanQuestionBlock ) {
		return null;
	}

	return booleanQuestionBlock;
};

export const focusOnQuizBlock = () => {
	const quizBlock = getQuizBlock();
	if ( ! quizBlock ) {
		return;
	}
	dispatch( blockEditorStore ).selectBlock( quizBlock.clientId );
};

export const focusOnQuestionBlock = () => {
	const questionBlock = getFirstQuestionBlock();
	if ( ! questionBlock ) {
		return;
	}
	dispatch( blockEditorStore ).selectBlock( questionBlock.clientId );
};

export const focusOnBooleanQuestionBlock = () => {
	const questionBlock = getFirstBooleanQuestionBlock();
	if ( ! questionBlock ) {
		return;
	}
	dispatch( blockEditorStore ).selectBlock( questionBlock.clientId );
};

export const ensureBooleanQuestionIsInEditor = () => {
	const questionBlock = getFirstBooleanQuestionBlock();

	if ( null === questionBlock ) {
		insertBooleanQuestion();
	}
};

const insertBooleanQuestion = () => {
	const quizBlock = getQuizBlock();
	if ( quizBlock ) {
		const { insertBlock } = dispatch( blockEditorStore );

		insertBlock(
			createBlock( 'sensei-lms/quiz-question', {
				type: 'boolean',
			} ),
			0,
			quizBlock.clientId
		);
	}
};

export const beforeEach = ( step ) => {
	// Close answer feedback as the happy path next step.
	if ( 'adding-answer-feedback' !== step.slug ) {
		const answerFeedbackButton = document.querySelector(
			'.sensei-lms-question-block__answer-feedback-toggle__header'
		);

		// Click to close only when it's open.
		if (
			answerFeedbackButton &&
			document.querySelector(
				'.wp-block-sensei-lms-quiz-question.show-answer-feedback'
			)
		) {
			answerFeedbackButton.click();
		}
	}

	// Close sidebar if's a mobile viewport.
	const viewportWidth =
		window.innerWidth || document.documentElement.clientWidth;

	if ( viewportWidth < 782 ) {
		const { closeGeneralSidebar } = dispatch( editPostStore );
		closeGeneralSidebar();
	}
};

/**
 * Returns the tour steps for the Quiz block.
 *
 * @return {Array.<TourStep>} An array containing the tour steps.
 */
export default function getTourSteps() {
	return [
		{
			slug: 'welcome',
			meta: {
				heading: __( 'Welcome to the Quiz block', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Take this short tour to learn the fundamentals of the Quiz block.',
						'sensei-lms'
					),
					mobile: null,
				},
				referenceElements: {
					desktop: '',
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			referenceElements: {
				desktop: '',
			},
			action: () => {
				performStepActionsAsync( [
					// Focus on the Quiz block.
					{
						action: () => {
							focusOnQuizBlock();
						},
					},
					// Highlight quiz block.
					{
						action: () => {
							const quizBlockSelector =
								'[data-type="sensei-lms/quiz"]';

							highlightElementsWithBorders( [
								quizBlockSelector,
							] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'change-question-type',
			meta: {
				heading: __( 'Changing the question type', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'There are a variety of question types for you to choose from including Multiple Choice, True/False and File Upload. We’ll show how to configure a True/False question, but the other question types work similarly.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			referenceElements: {
				desktop: '',
			},
			action: () => {
				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							focusOnQuestionBlock();
						},
					},
					// Click on type selector.
					{
						action: () => {
							const typeSelectorSelector =
								'.sensei-lms-question-block__type-selector button';

							const typeSelectorButton =
								document.querySelector( typeSelectorSelector );

							highlightElementsWithBorders( [
								typeSelectorSelector,
							] );

							if ( typeSelectorButton ) {
								typeSelectorButton.click();
							}
						},
						delay: 400,
					},
					// Highlight options and select true/false type.
					{
						action: () => {
							highlightElementsWithBorders( [
								'.sensei-lms-question-block__type-selector__popover',
							] );

							const questionBlock = getFirstQuestionBlock();

							dispatch( blockEditorStore ).updateBlockAttributes(
								questionBlock.clientId,
								{
									type: 'boolean',
								}
							);
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'adding-a-question',
			meta: {
				heading: __( 'Adding a question', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click on an existing question to select it. Then type your question in the Question Title field.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							focusOnQuestionBlock();
						},
					},
					// Focus on title field.
					{
						action: () => {
							const titleFieldSelector =
								'.sensei-lms-question-block__title .sensei-lms-single-line-input';

							const titleField =
								document.querySelector( titleFieldSelector );

							highlightElementsWithBorders( [
								titleFieldSelector,
							] );

							if ( titleField ) {
								titleField.focus();
							}
							scrollToCenter( titleFieldSelector );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'adding-question-description',
			meta: {
				heading: __( 'Adding a question description', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Enter any additional details about the question in the Question Description. This is optional.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				const descriptionFieldSelector =
					'.wp-block-sensei-lms-question-description .rich-text';

				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							focusOnQuestionBlock();
						},
					},
					// Focus on description field.
					{
						action: () => {
							const descriptionField = document.querySelector(
								descriptionFieldSelector
							);

							if ( descriptionField ) {
								descriptionField.focus();
							}
							scrollToCenter( descriptionFieldSelector );
						},
						delay: 400,
					},
					// Highlight description field.
					{
						action: () => {
							highlightElementsWithBorders( [
								descriptionFieldSelector,
							] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'setting-correct-answer',
			meta: {
				heading: __( 'Setting the correct answer', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click the Right or Wrong toggle to set the correct answer.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							ensureBooleanQuestionIsInEditor();
							focusOnBooleanQuestionBlock();
						},
					},
					// Highlight and focus correct answer toggle.
					{
						action: () => {
							highlightElementsWithBorders( [
								'.sensei-lms-question-block__answer--true-false__option:nth-child(1) .sensei-lms-question-block__answer--true-false__toggle',
								'.sensei-lms-question-block__answer--true-false__option:nth-child(2) .sensei-lms-question-block__answer--true-false__toggle',
							] );

							const toggleButton = document.querySelector(
								'.sensei-lms-question-block__answer--true-false__toggle'
							);
							if ( toggleButton ) {
								toggleButton.focus();
							}
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'adding-answer-feedback',
			meta: {
				heading: __( 'Adding answer feedback', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Add feedback by opening the Answer Feedback section of the question block. This feedback is shown after the quiz has been graded.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				const answerFeedbackButtonSelector =
					'.sensei-lms-question-block__answer-feedback-toggle__header';

				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							ensureBooleanQuestionIsInEditor();
							focusOnBooleanQuestionBlock();
						},
					},
					// Highlight answer feedback.
					{
						action: () => {
							highlightElementsWithBorders( [
								answerFeedbackButtonSelector,
							] );
						},
						delay: 400,
					},
					// Open answer feedback.
					{
						action: () => {
							const answerFeedbackButton = document.querySelector(
								answerFeedbackButtonSelector
							);

							// Open answer feedback if it's not already open.
							if (
								null ===
									document.querySelector(
										'.wp-block-sensei-lms-quiz-question.is-selected.show-answer-feedback'
									) &&
								answerFeedbackButton
							) {
								answerFeedbackButton.focus();
								answerFeedbackButton.click();
							}
						},
						delay: 400,
					},
					// Focus on answer feedback field and highlight answer feedback areas.
					{
						action: () => {
							const correctAnswerFieldSelector =
								'.sensei-lms-question__answer-feedback__content .block-editor-rich-text__editable';
							const correctAnswerField = document.querySelector(
								correctAnswerFieldSelector
							);

							correctAnswerField.focus();

							scrollToCenter( correctAnswerFieldSelector );

							highlightElementsWithBorders( [
								'.sensei-lms-question__answer-feedback--correct',
								'.sensei-lms-question__answer-feedback--incorrect',
							] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'adding-a-new-or-existing-question',
			meta: {
				heading: __(
					'Adding a new or existing question',
					'sensei-lms'
				),
				descriptions: {
					desktop: __(
						'Click + to open the inserter. Then click the New Question or Existing Question(s) option.',
						'sensei-lms'
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				const inserterSelector =
					'.wp-block-sensei-lms-quiz .block-editor-inserter__toggle';
				const popoverSelector = '.components-dropdown-menu__popover';

				performStepActionsAsync( [
					// Focus on quiz block.
					{
						action: () => {
							focusOnQuizBlock();
						},
					},
					// Click on the inserter.
					{
						action: () => {
							const inserter =
								document.querySelector( inserterSelector );
							if ( inserter ) {
								inserter.click();
							}
						},
						delay: 400,
					},
					// Highlight inserter button.
					{
						action: () => {
							scrollToCenter( inserterSelector );
							highlightElementsWithBorders( [
								inserterSelector,
							] );
						},
					},
					// Highlight options.
					{
						action: () => {
							highlightElementsWithBorders( [ popoverSelector ] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'configure-question-settings',
			meta: {
				heading: __(
					'Configuring the question settings',
					'sensei-lms'
				),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							'Question settings are available in the sidebar after selecting a question. View the <link_to_question_block_doc>doc</link_to_question_block_doc> to learn more about each one.',
							'sensei-lms'
						),
						{
							link_to_question_block_doc: (
								<ExternalLink
									href="https://senseilms.com/documentation/questions/#settings"
									children={ null }
								/>
							),
						}
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				performStepActionsAsync( [
					// Focus on question block.
					{
						action: () => {
							focusOnQuestionBlock();
						},
					},
					// Highlight question block and open sidebar settings.
					{
						action: () => {
							highlightElementsWithBorders( [
								'.wp-block-sensei-lms-quiz-question',
							] );

							const { openGeneralSidebar } =
								dispatch( editPostStore );

							openGeneralSidebar( 'edit-post/block' );
						},
						delay: 400,
					},
					// Highlight sidebar.
					{
						action: () => {
							const sidebarSelector =
								'.block-editor-block-inspector .components-panel__body';
							highlightElementsWithBorders(
								[ sidebarSelector ],
								'inset'
							);
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'configure-quiz-settings',
			meta: {
				heading: __( 'Configuring the quiz settings', 'sensei-lms' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							'Quiz settings are available in the sidebar after clicking the Quiz settings link. View the <link_to_quiz_block_doc>doc</link_to_quiz_block_doc> to learn more about each one.',
							'sensei-lms'
						),
						{
							link_to_quiz_block_doc: (
								<ExternalLink
									href="https://senseilms.com/documentation/quizzes/#quiz-settings"
									children={ null }
								/>
							),
						}
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				const settingsButtonSelector =
					'.sensei-lms-quiz-block__settings-quick-nav button';

				performStepActionsAsync( [
					// Focus on quiz block.
					{
						action: () => {
							focusOnQuizBlock();
						},
					},
					// Click on settings to open.
					{
						action: () => {
							const settingsButton = document.querySelector(
								settingsButtonSelector
							);

							if ( settingsButton ) {
								settingsButton.focus();
								settingsButton.click();
							}
						},
						delay: 400,
					},
					// Highlight settings button.
					{
						action: () => {
							highlightElementsWithBorders( [
								settingsButtonSelector,
							] );
						},
					},
					// Highlight sidebar.
					{
						action: () => {
							const sidebarSelector =
								'.sensei-lms-quiz-block__settings-wrapper';
							highlightElementsWithBorders(
								[ sidebarSelector ],
								'inset'
							);
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'congratulations',
			meta: {
				heading: __( 'Congratulations!', 'sensei-lms' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							"You've mastered the basics. View the quiz <link_to_quiz_doc>docs</link_to_quiz_doc> to learn more",
							'sensei-lms'
						),
						{
							link_to_quiz_doc: (
								<ExternalLink
									href="https://senseilms.com/documentation/quizzes/"
									children={ null }
								/>
							),
						}
					),
					mobile: null,
				},
			},
			options: {
				classNames: {
					desktop: '',
					mobile: '',
				},
			},
			action: () => {
				// Run the clean up.
				performStepActionsAsync( [] );
			},
		},
	];
}
