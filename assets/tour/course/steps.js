/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * A single tour step.
 *
 * @typedef {Object} TourStep
 *
 * @property {string}      slug                           - Identifier slug of the tour step.
 * @property {Object}      meta                           - Metadata about the tour step.
 * @property {string}      meta.heading                   - The title of the step.
 * @property {Object}      meta.descriptions              - Descriptions for different platforms.
 * @property {string}      meta.descriptions.desktop      - Desktop description.
 * @property {string|null} meta.descriptions.mobile       - Mobile description.
 * @property {Object}      meta.referenceElements         - Reference elements for different platforms.
 * @property {string}      meta.referenceElements.desktop - Reference element for desktop.
 * @property {Object}      options                        - Additional options for the tour step.
 * @property {Object}      options.classNames             - Class names for different platforms.
 * @property {string}      options.classNames.desktop     - Class name for desktop.
 * @property {string}      options.classNames.mobile      - Class name for mobile.
 */

/**
 * Returns the tour steps for the Course Outline block.
 *
 * @return {Array.<TourStep>} An array containing the tour steps.
 */
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
		{
			slug: 'congratulations',
			meta: {
				heading: __( 'Congratulations!', 'sensei-lms' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							"You've mastered the basics. View the <link_to_course_outline_block_doc>course outline docs</link_to_course_outline_block_doc> to learn more. <tour_restart_url>Restart tour</tour_restart_url>",
							'sensei-lms'
						),
						{
							link_to_course_outline_block_doc: (
								<ExternalLink
									href="https://senseilms.com/documentation/courses/#course-outline"
									children={ null }
								/>
							),
							tour_restart_url: <span></span>,
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

export default getTourSteps;
