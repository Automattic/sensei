/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { category as icon } from '@wordpress/icons';

import metadata from './block.json';
import edit from './course-categories-edit';
import save from './course-categories-save';

export default {
	...metadata,
	title: __( 'Course Categories', 'sensei-lms' ),
	description: __( 'Show the course categories', 'sensei-lms' ),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Lessons', 'sensei-lms' ),
		__( 'Categories', 'sensei-lms' ),
	],
	example: {
		attributes: {
			previewCategories: [
				{
					id: 1,
					name: __( 'Music', 'sensei-lms' ),
				},
				{
					id: 2,
					name: __( 'Movies', 'sensei-lms' ),
				},
			],
		},
	},
	icon,
	edit,
	save,
};
