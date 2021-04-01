/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProgressIcon as icon } from '../../icons';
import edit from './course-progress-edit';
import metadata from './block.json';

export default {
	title: __( 'Course Progress', 'sensei-lms' ),
	description: __(
		"Display the user's progress in the course. This block is only displayed if the user is enrolled.",
		'sensei-lms'
	),
	keywords: [
		__( 'Progress', 'sensei-lms' ),
		__( 'Bar', 'sensei-lms' ),
		__( 'Course', 'sensei-lms' ),
	],
	icon,
	...metadata,
	edit,
};
