/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';

export const registerCourseCompletedActionsBlock = () =>
	registerBlockVariation( 'core/buttons', {
		name: 'sensei-lms/course-completed-actions',
		title: __( 'Course Completed Actions', 'sensei-lms' ),
		description: __(
			'Prompt learners to take action after completing a course.',
			'sensei-lms'
		),
		category: 'sensei-lms',
		keywords: [
			__( 'Course', 'sensei-lms' ),
			__( 'Completed', 'sensei-lms' ),
			__( 'Actions', 'sensei-lms' ),
			__( 'Buttons', 'sensei-lms' ),
		],
		innerBlocks: applyFilters( 'sensei-lms.Course.completedActions', [
			[
				'core/button',
				{
					className: 'more-courses',
					text: __( 'Find More Courses', 'sensei-lms' ),
				},
			],
		] ),
	} );
