/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { category as icon } from '@wordpress/icons';

import metadata from './block.json';
import edit from './course-categories-edit';
import save from './course-categories-save';

export default {
	...metadata,
	metadata,
	example: {
		attributes: {
			previewCategories: [
				{
					id: 1,
					name: __( 'Music', 'sensei-lms' ),
				},
				{
					id: 2,
					name: __( 'Movies', 'sensei-lms' ),
				},
			],
		},
	},
	icon,
	edit,
	save,
};
