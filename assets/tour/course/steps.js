/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

function getTourSteps() {
	return [
		{
			slug: 'outline-block',
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
					desktop: '.edit-post-layout__metaboxes',
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
		},
		{
			slug: 'saving-lessons',
			meta: {
				heading: __( 'Saving lessons', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Click the “Save to edit lesson” option in the toolbar to save all lessons.',
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
			slug: 'editing-lesson',
			meta: {
				heading: __( 'Editing a lesson', 'sensei-lms' ),
				descriptions: {
					desktop: __(
						'Use the “Edit lesson” option in the toolbar to navigate to the lesson editor and add your content.',
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
	];
}

export default getTourSteps;
