import { BlockStyles, createButtonBlockType } from '../button';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	settings: {
		name: 'sensei-lms/button-contact-teacher',
		title: 'Contact teacher',
		attributes: {
			text: {
				default: 'Contact teacher',
			},
		},
		styles: [ BlockStyles.Fill, BlockStyles.Outline, BlockStyles.Link ],
	},
} );
