import { __ } from '@wordpress/i18n';
import { createButtonBlockType } from '../button';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	tagName: 'button',
	settings: {
		name: 'sensei-lms/button-take-course',
		title: __( 'Take Course', 'sensei-lms' ),
		description: __(
			'Allows the learner to start the course. Only displayed to users not already enrolled.',
			'sensei-lms'
		),
		keywords: [
			__( 'Start', 'sensei-lms' ),
			__( 'Sign up', 'sensei-lms' ),
			__( 'Enrol', 'sensei-lms' ),
			__( 'Enroll', 'sensei-lms' ),
			__( 'Course', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: 'Take Course',
			},
		},
	},
} );
