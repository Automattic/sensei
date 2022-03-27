/**
 * WordPress dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { render } from '@wordpress/element';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Student action menu.
 *
 * @param {Object} props
 * @param {string} props.courseId Course ID.
 */
const StudentActionMenu = ( { courseId } ) => {
	// eslint-disable-next-line no-console
	console.log( courseId );

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __( 'Select an action', 'sensei-lms' ) }
			controls={ [
				{
					title: __( 'Add to Course', 'sensei-lms' ),
					onClick: () => {},
				},
				{
					title: __( 'Remove from Course', 'sensei-lms' ),
					onClick: () => {},
				},
				{
					title: __( 'Grading', 'sensei-lms' ),
					onClick: () => {},
				},
			] }
		/>
	);
};

Array.from( document.getElementsByClassName( 'student-action-menu' ) ).forEach(
	( actionMenu ) => {
		render(
			<StudentActionMenu courseId={ actionMenu.dataset.courseId } />,
			actionMenu
		);
	}
);
