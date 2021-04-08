/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../button';
import MessagesDisabledNotice from './messages-disabled-notice';

const attributes = {
	text: {
		default: __( 'My Messages', 'sensei-lms' ),
	},
};

/**
 * Learner messages button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	settings: {
		name: 'sensei-lms/button-learner-messages',
		description: __(
			'Enable a learner to view their messages. This block is only displayed if the learner is logged in and private messaging is enabled.',
			'sensei-lms'
		),
		title: __( 'Learner Messages Button', 'sensei-lms' ),
		attributes,
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
		deprecated: [
			{
				attributes,
				save: () => null,
			},
		],
	},
	EditWrapper: MessagesDisabledNotice,
} );
