import { __ } from '@wordpress/i18n';

import { createButtonBlockType } from '../../button';

/**
 * Next lesson button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-next-lesson',
		title: __( 'Next lesson', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable a user to move to the next lesson.',
			'sensei-lms'
		),
		keywords: [
			__( 'Next', 'sensei-lms' ),
			__( 'Continue', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: 'Next lesson',
			},
		},
	},
	alignmentOptions: {
		controls: [ 'left', 'full' ],
		default: 'left',
	},
} );
