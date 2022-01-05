/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../icons/course.svg';
import metadata from './block.json';
import edit from './course-results-edit';

export default {
	title: __( 'Course Results', 'sensei-lms' ),
	description: __(
		'Show course results to students on the course completion page.',
		'sensei-lms'
	),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Lessons', 'sensei-lms' ),
		__( 'Modules', 'sensei-lms' ),
		__( 'Results', 'sensei-lms' ),
		__( 'Completion', 'sensei-lms' ),
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
		attributes: {},
	},
	...metadata,
	icon,
	edit,
};
