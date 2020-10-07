import { buttonBlock } from './index';

/**
 * Take course button block.
 */
export default buttonBlock( {
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
