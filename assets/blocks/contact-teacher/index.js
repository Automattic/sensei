import { BlockStyles, createButtonBlockType } from '../button';
import { __ } from '@wordpress/i18n';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-contact-teacher',
		description: __(
			'Enable a registered user to contact the teacher. This block is only displayed if the user is logged in and private messaging is enabled.',
			'sensei-lms'
		),
		title: 'Contact Teacher',
		attributes: {
			text: {
				default: 'Contact Teacher',
			},
			buttonClassName: {
				default: 'sensei-collapsible__toggle',
			},
			buttonAttributes: {
				default: {
					type: 'submit',
				},
			},
		},
		styles: [ BlockStyles.Fill, BlockStyles.Outline, BlockStyles.Link ],
	},
} );
