/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { category as icon } from '@wordpress/icons';
import metadata from './block.json';
import edit from './course-list-filter-edit';

export default {
	...metadata,
	title: __( 'Course List Filter', 'sensei-lms' ),
	description: __( 'Filter courses in Course List block', 'sensei-lms' ),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Categories', 'sensei-lms' ),
		__( 'Lessons', 'sensei-lms' ),
		__( 'Featured', 'sensei-lms' ),
		__( 'Filter', 'sensei-lms' ),
	],
	icon,
	edit,
};
