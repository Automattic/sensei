/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TourStep } from '../types';
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import {
	highlightElementsWithBorders,
	performStepActionsAsync,
} from '../helper';

const getQuizBlock = () =>
	getFirstBlockByName(
		'sensei-lms/quiz',
		select( 'core/block-editor' ).getBlocks()
	);

const getFirstQuestionBlock = () =>
	getFirstBlockByName(
		'sensei-lms/quiz-question',
		select( 'core/block-editor' ).getBlocks()
	);

function focusOnQuizBlock() {
	const quizBlock = getQuizBlock();
	if ( ! quizBlock ) {
		return;
	}
	dispatch( 'core/editor' ).selectBlock( quizBlock.clientId );
}

function focusOnQuestionBlock() {
	const questionBlock = getFirstQuestionBlock();
	if ( ! questionBlock ) {
		return;
	}
	dispatch( 'core/editor' ).selectBlock( questionBlock.clientId );
}

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
			action: async () => {
				performStepActionsAsync( [
					// Focus on the Quiz block.
					{
						action: () => {
							focusOnQuizBlock();

							const quizBlockSelector =
								'[data-type="sensei-lms/quiz"]';

							highlightElementsWithBorders( [
								quizBlockSelector,
							] );
						},
						delay: 0,
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
			action: async () => {
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

							const typeSelectorButton = document.querySelector(
								typeSelectorSelector
							);

							highlightElementsWithBorders( [
								typeSelectorSelector,
							] );

							typeSelectorButton.click();
						},
						delay: 100,
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
		},
	];
}
