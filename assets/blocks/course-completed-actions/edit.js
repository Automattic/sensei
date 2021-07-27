/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Filter the block template.
 *
 * @param {Array} template Block template.
 */
const TEMPLATE = [
	[
		'core/buttons',
		[],
		applyFilters( 'sensei-lms.Course.completedActions', [
			[
				'core/button',
				{
					text: __( 'Find More Courses', 'sensei-lms' ),
					url:
						window.sensei_course_completed_actions
							.course_archive_page_url,
				},
			],
		] ),
	],
];
const ALLOWED_BLOCKS = [ 'core/buttons' ];

/**
 * Edit Course Completed Actions block.
 */
const CourseCompletedActionsEdit = () => (
	<InnerBlocks allowedBlocks={ ALLOWED_BLOCKS } template={ TEMPLATE } />
);

export default CourseCompletedActionsEdit;
