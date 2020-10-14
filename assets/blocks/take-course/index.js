import { createButtonBlockType } from '../button';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-take-course',
		title: 'Take Course',
		attributes: {
			text: {
				default: 'Take Course',
			},
		},
	},
} );
