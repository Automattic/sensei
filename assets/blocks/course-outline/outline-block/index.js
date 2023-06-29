/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../../icons/course.svg';
import metadata from './block.json';
import edit from './outline-edit';
import save from './outline-save';

export default {
	...metadata,
	metadata,
	title: __( 'Course Outline', 'sensei-lms' ),
	description: __( 'Manage your Sensei LMS course outline.', 'sensei-lms' ),
	keywords: [
		__( 'course', 'sensei-lms' ),
		__( 'lessons', 'sensei-lms' ),
		__( 'modules', 'sensei-lms' ),
		__( 'outline', 'sensei-lms' ),
		__( 'structure', 'sensei-lms' ),
	],
	styles: [
		{
			name: 'default',
			label: __( 'Filled', 'sensei-lms' ),
			isDefault: true,
		},
		{
			name: 'minimal',
			label: __( 'Minimal', 'sensei-lms' ),
		},
	],
	example: {
		attributes: {
			isPreview: true,
		},
		innerBlocks: [
			{
				name: 'sensei-lms/course-outline-module',
				attributes: {
					title: __( 'Module', 'sensei-lms' ),
					description: __( 'About Module', 'sensei-lms' ),
				},
				innerBlocks: [
					{
						name: 'sensei-lms/course-outline-lesson',
						attributes: {
							title: __( 'Lesson', 'sensei-lms' ),
							id: 1,
							draft: false,
							isExample: true,
						},
					},
				],
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'First Lesson', 'sensei-lms' ),
					id: 2,
					draft: false,
					isExample: true,
				},
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'Second Lesson', 'sensei-lms' ),
					id: 3,
					draft: false,
					isExample: true,
				},
			},
		],
	},
	icon,
	edit,
	save,
};
