/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LessonPropertiesIcon as icon } from '../../icons';
import metadata from './block.json';
import edit from './lesson-properties-edit';

export default {
	title: __( 'Lesson Properties', 'sensei-lms' ),
	description: __(
		'Add lesson properties such as length and difficulty.',
		'sensei-lms'
	),
	keywords: [
		__( 'Metadata', 'sensei-lms' ),
		__( 'Length', 'sensei-lms' ),
		__( 'Complexity', 'sensei-lms' ),
		__( 'Difficulty', 'sensei-lms' ),
		__( 'Lesson Information', 'sensei-lms' ),
		__( 'Lesson Properties', 'sensei-lms' ),
	],
	icon,
	...metadata,
	example: {
		attributes: {
			difficulty: 'easy',
			length: 10,
		},
	},
	edit,
	save: () => {
		return null;
	},
};
