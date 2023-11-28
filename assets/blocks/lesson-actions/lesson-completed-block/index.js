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
		name: 'sensei-lms/button-lesson-completed',
		parent: [ 'sensei-lms/lesson-actions' ],
		title: __( 'Lesson Completed', 'sensei-lms' ),
		description: __(
			'This button becomes visible only when a lesson is completed. It has no other functionality other than indicating that the lesson is completed',
			'sensei-lms'
		),
		keywords: [
			__( 'Completed', 'sensei-lms' ),
			__( 'Finished', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'Completed', 'sensei-lms' ),
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
} );
