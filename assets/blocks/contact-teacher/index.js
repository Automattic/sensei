import { BlockStyles, createButtonBlockType } from '../button';
import { __ } from '@wordpress/i18n';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	settings: {
		name: 'sensei-lms/button-contact-teacher',
		description: __(
			'Allows the learner to contact the teacher. Only displayed to logged in users when private messaging is enabled.',
			'sensei-lms'
		),
		title: 'Contact teacher',
		attributes: {
			text: {
				default: 'Contact teacher',
			},
		},
		styles: [ BlockStyles.Fill, BlockStyles.Outline, BlockStyles.Link ],
	},
} );
