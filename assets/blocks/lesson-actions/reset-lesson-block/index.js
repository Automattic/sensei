/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../../button';

/**
 * Reset lesson button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-reset-lesson',
		title: __( 'Reset Lesson', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable a learner to reset their progress. This block is only displayed if the lesson is completed and has no quiz, or the quiz is completed and retakes are enabled.',
			'sensei-lms'
		),
		keywords: [
			__( 'Reset', 'sensei-lms' ),
			__( 'Restart', 'sensei-lms' ),
			__( 'Revert', 'sensei-lms' ),
			__( 'Progress', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'Reset Lesson', 'sensei-lms' ),
			},
			buttonClassName: {
				default: [ 'sensei-stop-double-submission' ],
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
} );
