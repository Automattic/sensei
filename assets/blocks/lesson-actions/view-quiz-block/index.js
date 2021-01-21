import { __ } from '@wordpress/i18n';

import { createButtonBlockType } from '../../button';

/**
 * View quiz button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-view-quiz',
		title: __( 'View quiz', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			"Enable an enrolled user to take a lesson's quiz.",
			'sensei-lms'
		),
		keywords: [
			__( 'Quiz', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: 'View quiz',
			},
		},
	},
} );
