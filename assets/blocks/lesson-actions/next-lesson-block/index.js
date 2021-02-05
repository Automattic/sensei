/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../../button';

/**
 * Next lesson button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-next-lesson',
		title: __( 'Next Lesson', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable a learner to move to the next lesson. This block is only displayed if the current lesson has been completed.',
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
				default: __( 'Next Lesson', 'sensei-lms' ),
			},
		},
		styles: [
			{ ...BlockStyles.Fill, isDefault: true },
			BlockStyles.Outline,
			BlockStyles.Link,
		],
	},
} );
