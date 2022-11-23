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
