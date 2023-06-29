/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../icons/lesson-properties.svg';
import metadata from './block.json';
import edit from './lesson-properties-edit';

export default {
	...metadata,
	metadata,
	title: __( 'Lesson Properties', 'sensei-lms' ),
	description: __(
		'Add lesson properties such as length and difficulty.',
		'sensei-lms'
	),
	keywords: [
		__( 'metadata', 'sensei-lms' ),
		__( 'length', 'sensei-lms' ),
		__( 'complexity', 'sensei-lms' ),
		__( 'difficulty', 'sensei-lms' ),
		__( 'lesson information', 'sensei-lms' ),
		__( 'lesson properties', 'sensei-lms' ),
	],
	icon,
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
