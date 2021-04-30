/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './lesson-properties-edit';

export default {
	title: __( 'Lesson Properties', 'sensei-lms' ),
	description: __(
		'Add lesson properties such as length and difficulty.',
		'sensei-lms'
	),
	...metadata,
	edit,
	save: () => {
		return null;
	},
};
