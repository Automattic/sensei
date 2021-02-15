/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../../button';

/**
 * View quiz button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-view-quiz',
		title: __( 'View Quiz', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __( 'Enable a learner to view the quiz.', 'sensei-lms' ),
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
		styles: [
			{ ...BlockStyles.Fill, isDefault: true },
			BlockStyles.Outline,
			BlockStyles.Link,
		],
	},
} );
