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
	title: __( 'Course List Featured Filter', 'sensei-lms' ),
	description: __( 'Filter the Course List by Featured', 'sensei-lms' ),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Lessons', 'sensei-lms' ),
		__( 'Featured', 'sensei-lms' ),
	],
	icon,
	edit,
};
