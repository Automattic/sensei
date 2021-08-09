/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';

export const registerCourseCompletedActionsBlock = () => registerBlockVariation(
	'core/buttons', {
		name: 'course-completed-actions',
		title: __( 'Course Completed Actions', 'sensei-lms' ),
		category: 'sensei-lms',
		innerBlocks: applyFilters( 'sensei-lms.Course.completedActions', [
			[
				'core/button',
				{
					text: __( 'Find More Courses', 'sensei-lms' ),
				},
			],
		] ),
	}
);
