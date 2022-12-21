/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../button';
import CourseStatusToolbar from '../course-actions-block/course-status-toolbar';

/**
 * View results button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	settings: {
		name: 'sensei-lms/button-view-results',
		description: __(
			'Enable a student to view their course results.',
			'sensei-lms'
		),
		title: __( 'View Results', 'sensei-lms' ),
		attributes: {
			text: {
				default: __( 'View Results', 'sensei-lms' ),
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
	invalidUsage: {
		message: __(
			'The View Results block can only be used inside the Course List block.',
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
