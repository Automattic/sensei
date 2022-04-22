/**
 * WordPress dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { render, useState } from '@wordpress/element';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StudentModal from '../student-modal';

/**
 * Student action menu.
 *
 * @param {Object} props
 * @param {string} props.studentId          Student's user id
 * @param {string} props.studentName        Student's user name.
 * @param {string} props.studentDisplayName Student's displayName.
 */
export const StudentActionMenu = ( {
	studentId,
	studentName,
	studentDisplayName,
} ) => {
	const [ action, setAction ] = useState( '' );
	const [ isModalOpen, setModalOpen ] = useState( false );
	const closeModal = ( needsReload ) => {
		if ( needsReload ) {
			window.location.reload();
		}
		setModalOpen( false );
	};

	const controls = [
		{
			title: __( 'Add to Course', 'sensei-lms' ),
			onClick: () => addToCourse(),
		},
		{
			title: __( 'Remove from Course', 'sensei-lms' ),
			onClick: () => removeFromCourse(),
		},
		{
			title: __( 'Reset or Remove Progress', 'sensei-lms' ),
			onClick: () => resetProgress(),
		},
		{
			title: __( 'Grading', 'sensei-lms' ),
			onClick: () =>
				window.open(
					`edit.php?post_type=course&page=sensei_grading&view=ungraded&s=${ studentName }`,
					'_self'
				),
		},
	];

	const addToCourse = () => {
		setAction( 'add' );
		setModalOpen( true );
	};

	const removeFromCourse = () => {
		setAction( 'remove' );
		setModalOpen( true );
	};

	const resetProgress = () => {
		setAction( 'reset-progress' );
		setModalOpen( true );
	};

	return (
		<>
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Select an action', 'sensei-lms' ) }
				controls={ controls }
			/>

			{ isModalOpen && (
				<StudentModal
					action={ action }
					onClose={ closeModal }
					students={ [ studentId ] }
					studentDisplayName={ studentDisplayName }
				/>
			) }
		</>
	);
};

Array.from( document.getElementsByClassName( 'student-action-menu' ) ).forEach(
	( actionMenu ) => {
		render(
			<StudentActionMenu
				studentId={ actionMenu?.dataset?.userId }
				studentName={ actionMenu?.dataset?.userName }
				studentDisplayName={ actionMenu?.dataset?.userDisplayName }
			/>,
			actionMenu
		);
	}
);
