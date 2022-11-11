/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../../icons/lesson.svg';
import edit from './lesson-edit';
import metadata from './block.json';

export default {
	...metadata,
	metadata,
	icon,
	example: {
		attributes: {
			title: __( 'Start learning', 'sensei-lms' ),
		},
	},
	edit,
};
