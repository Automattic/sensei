/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
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
	removeHighlightClasses,
	waitForElement,
} from '../helper';

const getCourseOutlineBlock = () =>
	getFirstBlockByName(
		'sensei-lms/course-outline',
		select( 'core/block-editor' ).getBlocks()
	);

function insertLessonBlock( lessonTitle ) {
	const courseOutlineBlock = getCourseOutlineBlock();
	if ( courseOutlineBlock ) {
		const { insertBlock } = dispatch( 'core/block-editor' );

		insertBlock(
			createBlock( 'sensei-lms/course-outline-lesson', {
				title: lessonTitle,
			} ),
			undefined,
			courseOutlineBlock.clientId
		);
	}
}

function focusOnCourseOutlineBlock() {
	const courseOutlineBlock = getCourseOutlineBlock();
	if ( ! courseOutlineBlock ) {
		return;
	}
	dispatch( 'core/editor' ).selectBlock( courseOutlineBlock.clientId );
}

async function ensureLessonBlocksIsInEditor() {
	const blankInserterSelector =
		'.wp-block-sensei-lms-course-outline__placeholder__option-blank';
	const lessonSelector = '.wp-block-sensei-lms-course-outline-lesson';

	const lesson = document.querySelector( lessonSelector );

	if ( lesson ) {
		return;
	}

	const blankInserter = document.querySelector( blankInserterSelector );

	// When the Course Outline block has the "Start with blank" option shown in it, instead of just inserting a lesson block,
	// We click on that option to insert the lesson block.
	if ( blankInserter ) {
		await performStepActionsAsync( [
			{
				action: () => {
					highlightElementsWithBorders( [ blankInserterSelector ] );
				},
			},
			{
				action: () => {
					blankInserter.click();
				},
				delay: 600,
			},
		] );
		return;
	}

	insertLessonBlock( 'Lesson 1' );
}

/**
 * Returns the tour steps for the Course Outline block.
 *
 * @return {Array.<TourStep>} An array containing the tour steps.
 */
function getTourSteps() {
	return [
		{
			slug: 'welcome',
			meta: {
				heading: __(
					'Welcome to the Course Outline block',
					'sensei-lms'
				),
				descriptions: {
					desktop: __(
						'Take this short tour to learn how to create your course outline right in the editor. Click an option in the block to get started.',
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
					{
						action: () => {
							focusOnCourseOutlineBlock();

							const courseOutlineBlockSelector =
								'[data-type="sensei-lms/course-outline"] div';

							highlightElementsWithBorders( [
								courseOutlineBlockSelector,
							] );
						},
						delay: 0,
					},
				] );
			},
		},
		{
			slug: 'renaming-existing-lesson',
			meta: {
				heading: __( 'Renaming an existing lesson', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click on an existing lesson to select it. Then give it a new name.',
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
				await ensureLessonBlocksIsInEditor();
				await performStepActionsAsync( [
					{
						action: () => {
							focusOnCourseOutlineBlock();
							const lesson = document.querySelector(
								'[data-type="sensei-lms/course-outline-lesson"]'
							);
							if ( lesson ) {
								lesson.focus();
							}
						},
					},
					{
						action: () => {
							const lesson = document.querySelector(
								'[data-type="sensei-lms/course-outline-lesson"] textarea'
							);
							if ( lesson ) {
								lesson.focus();
							}
							highlightElementsWithBorders( [
								'[data-type="sensei-lms/course-outline-lesson"] .wp-block-sensei-lms-course-outline-lesson',
							] );
						},
						delay: 100,
					},
				] );
			},
		},
		{
			slug: 'adding-new-module',
			meta: {
				heading: __( 'Adding a module', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'A module is a container for a group of related lessons in a course. Click + to open the inserter. Then click the Module option.',
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
			action: async () => {
				await ensureLessonBlocksIsInEditor();
				const inserterSelector =
					'.wp-block-sensei-lms-course-outline .block-editor-inserter__toggle';
				const moduleSelector =
					'.components-dropdown-menu__popover .components-dropdown-menu__menu .components-dropdown-menu__menu-item:last-child';
				await performStepActionsAsync( [
					{
						action: () => {
							// Necessary to focus on the Course Outline block here otherwise inserter won't appear.
							focusOnCourseOutlineBlock();
						},
					},
					{
						action: () => {
							const inserter = document.querySelector(
								inserterSelector
							);
							if ( inserter ) {
								inserter.click();
							}
						},
						delay: 400,
					},
					{
						action: () => {
							highlightElementsWithBorders( [
								inserterSelector,
							] );
						},
					},
					{
						action: () => {
							highlightElementsWithBorders( [ moduleSelector ] );
							const module = document.querySelector(
								moduleSelector
							);
							if ( module ) {
								module.focus();
							}
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'adding-new-lesson',
			meta: {
				heading: __( 'Adding a new lesson', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click + to open the inserter. Then click the Lesson option.',
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
			action: async () => {
				await ensureLessonBlocksIsInEditor();
				const inserterSelector =
					'.wp-block-sensei-lms-course-outline .block-editor-inserter__toggle';
				const insertLessonSelector =
					'.components-dropdown-menu__popover .components-dropdown-menu__menu .components-dropdown-menu__menu-item:first-child';
				performStepActionsAsync( [
					{
						action: () => {
							// Necessary to focus on the Course Outline block here otherwise inserter won't appear.
							focusOnCourseOutlineBlock();
						},
					},
					{
						action: () => {
							const inserter = document.querySelector(
								inserterSelector
							);
							if ( inserter ) {
								inserter.click();
							}
						},
						delay: 400,
					},
					{
						action: () => {
							highlightElementsWithBorders( [
								inserterSelector,
							] );
						},
					},
					{
						action: () => {
							highlightElementsWithBorders( [
								insertLessonSelector,
							] );
							const module = document.querySelector(
								insertLessonSelector
							);
							if ( module ) {
								module.focus();
							}
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'deleting-lesson',
			meta: {
				heading: __( 'Deleting a lesson', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Use the Options menu in the toolbar to delete a selected lesson.',
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
			action: async () => {
				await ensureLessonBlocksIsInEditor();
				const optionSelector =
					'.block-editor-block-contextual-toolbar .components-dropdown-menu.block-editor-block-settings-menu button';
				performStepActionsAsync( [
					{
						action: () => {
							const lesson = document.querySelector(
								'.wp-block-sensei-lms-course-outline-lesson'
							);
							if ( lesson ) {
								lesson.parentElement.focus();
							}
							highlightElementsWithBorders( [
								'[data-type="sensei-lms/course-outline-lesson"] .wp-block-sensei-lms-course-outline-lesson',
							] );
						},
					},
					{
						action: () => {
							highlightElementsWithBorders( [ optionSelector ] );
						},
						delay: 400,
					},
					{
						action: () => {
							const option = document.querySelector(
								optionSelector
							);
							if ( option ) {
								option.click();
							}
						},
						delay: 400,
					},
					{
						action: () => {
							highlightElementsWithBorders( [ optionSelector ] );
						},
					},
					{
						action: () => {
							const deleteButtonSelector =
								'.block-editor-block-settings-menu__popover.components-dropdown-menu__popover .components-menu-group:last-child .components-button.components-menu-item__button:last-child';
							const deleteButton = document.querySelector(
								deleteButtonSelector
							);
							if ( deleteButton ) {
								deleteButton.focus();
							}
							highlightElementsWithBorders( [
								deleteButtonSelector,
							] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'saving-lessons',
			meta: {
				heading: __( 'Saving lessons', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click the "Save to edit lesson" option in the toolbar to save all lessons.',
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
			action: async () => {
				await ensureLessonBlocksIsInEditor();
				const buttonSelector =
					'.block-editor-block-contextual-toolbar .wp-block-sensei-lms-course-outline-lesson__save';
				const unsavedLessonSelector =
					'.wp-block-sensei-lms-course-outline-lesson:not([data-lesson-id]):not(.is-auto-draft)';
				const additionalActionStep = [];

				const unsavedLesson = document.querySelector(
					unsavedLessonSelector
				);

				if ( ! unsavedLesson ) {
					additionalActionStep.push( {
						action: () => {
							insertLessonBlock(
								__( 'Unsaved new lesson', 'sensei-lms' )
							);
						},
					} );
				}

				performStepActionsAsync( [
					...additionalActionStep,
					{
						action: () => {
							const lesson = document.querySelector(
								unsavedLessonSelector
							);
							if ( lesson ) {
								lesson.parentElement.focus();
							}
							highlightElementsWithBorders( [
								unsavedLessonSelector,
							] );
						},
					},
					{
						action: () => {
							highlightElementsWithBorders( [ buttonSelector ] );
						},
						delay: 400,
					},
				] );
			},
		},
		{
			slug: 'editing-lesson',
			meta: {
				heading: __( 'Editing a lesson', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Use the "Edit lesson" option in the toolbar to navigate to the lesson editor and add your content.',
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
			action: async () => {
				await ensureLessonBlocksIsInEditor();
				const buttonSelector =
					'.block-editor-block-contextual-toolbar .block-editor-block-toolbar .wp-block-sensei-lms-course-outline-lesson__edit';
				const savedlessonSelector =
					'.wp-block-sensei-lms-course-outline-lesson[data-lesson-id]';

				const savedLesson = document.querySelector(
					savedlessonSelector
				);

				if ( ! savedLesson ) {
					const { savePost } = dispatch( 'core/editor' );
					savePost();
					await waitForElement( savedlessonSelector, 15 );
				}

				performStepActionsAsync( [
					{
						action: () => {
							const lesson = document.querySelector(
								savedlessonSelector
							);
							if ( lesson ) {
								lesson.parentElement.focus();
							}
							highlightElementsWithBorders( [
								savedlessonSelector,
							] );
						},
					},
					{
						action: () => {
							highlightElementsWithBorders( [ buttonSelector ] );
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
							"You've mastered the basics. View the <link_to_course_outline_block_doc>course outline docs</link_to_course_outline_block_doc> to learn more.",
							'sensei-lms'
						),
						{
							link_to_course_outline_block_doc: (
								<ExternalLink
									href="https://senseilms.com/documentation/courses/#course-outline"
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
				removeHighlightClasses();
			},
		},
	];
}

export default getTourSteps;
