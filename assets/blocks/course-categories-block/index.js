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

export default {
	...metadata,
	title: __( 'Course Categories', 'sensei-lms' ),
	description: __( 'Show the course categories', 'sensei-lms' ),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Lessons', 'sensei-lms' ),
		__( 'Categories', 'sensei-lms' ),
	],
	icon,
	edit,
};
