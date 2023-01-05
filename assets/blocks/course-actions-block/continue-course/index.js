/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../../button';
import CourseStatusToolbar from '../course-status-toolbar';

/**
 * Continue Course block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	settings: {
		name: 'sensei-lms/button-continue-course',
		parent: [ 'sensei-lms/course-actions' ],
		title: __( 'Continue Course', 'sensei-lms' ),
		description: __(
			'Enable a student to pick up where they left off in a course.',
			'sensei-lms'
		),
		keywords: [
			__( 'Button', 'sensei-lms' ),
			__( 'Continue', 'sensei-lms' ),
			__( 'Course', 'sensei-lms' ),
		],
		attributes: {
			text: {
				default: __( 'Continue', 'sensei-lms' ),
			},
		},
		styles: [
			{ ...BlockStyles.Fill, isDefault: true },
			BlockStyles.Outline,
			BlockStyles.Link,
		],
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
