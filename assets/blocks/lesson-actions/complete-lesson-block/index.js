/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../../button';

/**
 * Complete lesson button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-complete-lesson',
		parent: [ 'sensei-lms/lesson-actions' ],
		title: __( 'Complete Lesson', 'sensei-lms' ),
		description: __(
			'Enable a learner to mark the lesson as complete. This block is only displayed if the lesson has no quiz or the quiz is optional.',
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
				default: __( 'Complete Lesson', 'sensei-lms' ),
			},
			buttonClassName: {
				default: [ 'sensei-stop-double-submission' ],
			},
		},
		styles: [
			{ ...BlockStyles.Fill, isDefault: true },
			BlockStyles.Outline,
			BlockStyles.Link,
		],
	},
} );
