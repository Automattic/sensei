/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../icons/course-list-filter.svg';
import metadata from './block.json';
import edit from './course-list-filter-edit';

export default {
	...metadata,
	metadata,
	title: __( 'Course List Filter', 'sensei-lms' ),
	description: __( 'Filter courses in Course List block', 'sensei-lms' ),
	keywords: [
		__( 'course', 'sensei-lms' ),
		__( 'categories', 'sensei-lms' ),
		__( 'lessons', 'sensei-lms' ),
		__( 'featured', 'sensei-lms' ),
		__( 'filter', 'sensei-lms' ),
	],
	icon,
	edit,
};
