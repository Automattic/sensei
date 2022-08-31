/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './course-featured-edit';

export default {
	...metadata,
	title: __( 'Course Featured', 'sensei-lms' ),
	description: __( 'Show the course categories', 'sensei-lms' ),
	keywords: [
		// __( 'Course', 'sensei-lms' ),
		// __( 'Lessons', 'sensei-lms' ),
		// __( 'Categories', 'sensei-lms' ),
	],
	edit,
};
