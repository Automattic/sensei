/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './lesson-metadata-edit';

export default {
	title: __( 'Lesson Metadata', 'sensei-lms' ),
	description: __( 'Add lesson details such as complexity and length.', 'sensei-lms' ),
	...metadata,
	edit,
	save: () => {
		return null;
	}
};
