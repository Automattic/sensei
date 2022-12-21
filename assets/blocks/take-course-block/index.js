/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { createButtonBlockType } from '../button';
import CourseStatusToolbar from '../course-actions-block/course-status-toolbar';

/**
 * Take course button block.
 */
export default createButtonBlockType( {
	settings: {
		name: 'sensei-lms/button-take-course',
		title: __( 'Course Signup', 'sensei-lms' ),
		description: __(
			'Enable a registered user to start the course. This block is only displayed if the user is not already enrolled.',
			'sensei-lms'
		),
		keywords: [
			__( 'Start', 'sensei-lms' ),
			__( 'Sign up', 'sensei-lms' ),
			__( 'Signup', 'sensei-lms' ),
			__( 'Enrol', 'sensei-lms' ),
			__( 'Enroll', 'sensei-lms' ),
			__( 'Course', 'sensei-lms' ),
			__( 'Take course', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'Take Course', 'sensei-lms' ),
			},
		},
	},
	invalidUsage: {
		message: __(
			'The Course Signup block can only be used inside the Course List block.',
			'sensei-lms'
		),
		validPostTypes: [ 'course' ],
	},
	EditWrapper: ( { children } ) => (
		<>
			<BlockControls>
				<CourseStatusToolbar useCourseStatusContext={ true } />
			</BlockControls>
			{ children }
		</>
	),
} );
