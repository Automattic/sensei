import { __ } from '@wordpress/i18n';

import { BlockStyles, createButtonBlockType } from '../../button';

/**
 * Reset lesson button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-reset-lesson',
		title: __( 'Reset lesson', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable an enrolled user to reset their progress.',
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
				default: 'Reset lesson',
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
} );
