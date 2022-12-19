/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ToolbarDropdown from '../editor-components/toolbar-dropdown';
import { useCallback } from 'react';

const toolbarOptions = [
	{
		label: __( 'Enrolled', 'sensei-lms' ),
		value: 'enrolled',
	},
	{
		label: __( 'In Progress', 'sensei-lms' ),
		value: 'in-progress',
	},
	{
		label: __( 'Completed', 'sensei-lms' ),
		value: 'completed',
	},
];

/**
 * A hook for child blocks to set the parent Course Actions block's course status.
 *
 * @param {string} clientId The child block's client ID.
 * @return {Function}       Callback function to set the course status.
 */
export const useSetCourseStatusOnCourseActionsBlock = ( clientId ) => {
	const select = useSelect( 'core/block-editor' );
	const dispatch = useDispatch( 'core/block-editor' );

	return useCallback(
		( status ) => {
			const courseActionsBlockClientID = select.getBlockParentsByBlockName(
				clientId,
				'sensei-lms/course-actions',
				true
			)[ 0 ];

			dispatch.updateBlockAttributes( courseActionsBlockClientID, {
				courseStatus: status,
			} );
		},
		[ clientId, select, dispatch ]
	);
};

/**
 * Toolbar component for the Course State. It can be Enrolled (the default), In
 * Progress, or Completed.
 *
 * @param {Object}   props
 * @param {string}   props.courseStatus    Course status.
 * @param {Function} props.setCourseStatus Function to set the course status.
 */
const CourseStatusToolbar = ( { courseStatus, setCourseStatus } ) => {
	if ( ! courseStatus ) {
		courseStatus = toolbarOptions[ 0 ].value;
	}

	return (
		<Toolbar>
			<ToolbarDropdown
				options={ toolbarOptions }
				optionsLabel="Course Status"
				value={ courseStatus }
				onChange={ setCourseStatus }
			/>
		</Toolbar>
	);
};

export default CourseStatusToolbar;
