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
 */
export const StudentActionMenu = () => {
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
			title: __( 'Grading', 'sensei-lms' ),
			onClick: () => {},
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

	return (
		<>
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Select an action', 'sensei-lms' ) }
				controls={ controls }
			/>

			{ isModalOpen && (
				<StudentModal action={ action } onClose={ closeModal } />
			) }
		</>
	);
};

Array.from( document.getElementsByClassName( 'student-action-menu' ) ).forEach(
	( actionMenu ) => {
		render(
			<StudentActionMenu userId={ actionMenu?.dataset?.userId } />,
			actionMenu
		);
	}
);
