import { __ } from '@wordpress/i18n';

import { createButtonBlockType } from '../../button';

/**
 * Complete lesson button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-complete-lesson',
		parent: [ 'sensei-lms/lesson-actions' ],
		title: __( 'Complete lesson', 'sensei-lms' ),
		description: __(
			'Enable an enrolled user to complete the lesson.',
			'sensei-lms'
		),
		keywords: [
			__( 'Complete', 'sensei-lms' ),
			__( 'Finish', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: 'Complete lesson',
			},
		},
	},
} );
