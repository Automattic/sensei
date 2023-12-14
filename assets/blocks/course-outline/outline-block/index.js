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
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'Lesson 1', 'sensei-lms' ),
					id: 1,
					draft: false,
					isExample: true,
				},
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'Lesson 2', 'sensei-lms' ),
					id: 2,
					draft: false,
					isExample: true,
				},
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'Lesson 3', 'sensei-lms' ),
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
