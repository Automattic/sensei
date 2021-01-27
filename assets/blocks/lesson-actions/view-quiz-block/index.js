/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { createButtonBlockType } from '../../button';

/**
 * View quiz button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-view-quiz',
		title: __( 'View Quiz', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable an enrolled user to view the quiz.',
			'sensei-lms'
		),
		keywords: [
			__( 'Quiz', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'View Quiz', 'sensei-lms' ),
			},
		},
	},
} );
