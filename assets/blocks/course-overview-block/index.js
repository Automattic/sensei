/**
 * WordPress dependencies
 */
import { link as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './course-overview-edit';
import metadata from './block.json';

export default {
	...metadata,
	metadata,
	title: __( 'Course Overview', 'sensei-lms' ),
	description: __( 'Displays a link to the course.', 'sensei-lms' ),
	icon,
	edit,
};
