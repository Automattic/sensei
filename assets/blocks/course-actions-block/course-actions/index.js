/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './course-actions-edit';
import save from './course-actions-save';
import icon from '../../../icons/buttons.svg';

export default {
	...metadata,
	metadata,
	example: {
		innerBlocks: [
			{
				name: 'sensei-lms/button-take-course',
				attributes: {
					text: __( 'Start Course', 'sensei-lms' ),
				},
			},
		],
	},
	icon,
	edit,
	save,
};
