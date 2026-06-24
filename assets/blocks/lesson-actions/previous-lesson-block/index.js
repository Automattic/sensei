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
		name: 'sensei-lms/button-previous-lesson',
		title: __( 'Previous Lesson', 'sensei-lms' ),
		parent: [ 'sensei-lms/lesson-actions' ],
		description: __(
			'Enable a student to move to the previous lesson. This block is only displayed if there is a previous lesson has been completed.',
			'sensei-lms'
		),
		keywords: [
			__( 'Previous', 'sensei-lms' ),
			__( 'Continue', 'sensei-lms' ),
			__( 'Lesson', 'sensei-lms' ),
			__( 'Button', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'Previous Lesson', 'sensei-lms' ),
			},
		},
		styles: [
			{ ...BlockStyles.Fill, isDefault: true },
			BlockStyles.Outline,
			BlockStyles.Link,
		],
	},
} );
