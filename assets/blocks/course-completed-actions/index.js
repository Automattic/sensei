/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';

export const registerCourseCompletedActionsBlock = () => {
	const moreCoursesAttributes = {
		className: 'more-courses',
		text: __( 'Find More Courses', 'sensei-lms' ),
	};

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
			__( 'Find More Courses', 'sensei-lms' ),
			__( 'View Certificate', 'sensei-lms' ),
		],
		innerBlocks: applyFilters( 'sensei-lms.Course.completedActions', [
			[ 'core/button', moreCoursesAttributes ],
		] ),
		attributes: {
			contentJustification: 'center',
			anchor: 'course-completed-actions',
		},
		isActive: ( blockAttributes, variationAttributes ) =>
			blockAttributes.anchor === variationAttributes.anchor,
	} );

	registerBlockVariation( 'core/button', {
		name: 'sensei-lms/more-courses-button',
		title: __( 'Find More Courses', 'sensei-lms' ),
		description: __(
			'Prompt learners to find more courses.',
			'sensei-lms'
		),
		keywords: [
			__( 'Courses', 'sensei-lms' ),
			__( 'Archive', 'sensei-lms' ),
		],
		category: 'sensei-lms',
		attributes: moreCoursesAttributes,
		isActive: ( blockAttributes, variationAttributes ) =>
			blockAttributes.className?.match( variationAttributes.className ),
	} );
};
