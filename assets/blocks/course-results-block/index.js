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
	...metadata,
	metadata,
	title: __( 'Course Results', 'sensei-lms' ),
	description: __(
		'Show course results to students on the course completion page.',
		'sensei-lms'
	),
	keywords: [
		__( 'course', 'sensei-lms' ),
		__( 'lessons', 'sensei-lms' ),
		__( 'modules', 'sensei-lms' ),
		__( 'results', 'sensei-lms' ),
		__( 'completion', 'sensei-lms' ),
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
	icon,
	edit,
};
