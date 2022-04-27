/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { render, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StudentModal from '../student-modal';

/**
 *  Student bulk action button.
 */
export const StudentBulkActionButton = () => {
	const [ action, setAction ] = useState( 'add' );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ studentIds, setStudentIds ] = useState( [] );
	const [ studentName, setStudentName ] = useState( '' );
	const closeModal = ( needsReload ) => {
		if ( needsReload ) {
			window.location.reload();
		}
		setIsModalOpen( false );
	};
	const setActionValue = ( selectedValue ) => {
		switch ( selectedValue ) {
			case 'enrol_restore_enrolment':
				setAction( 'add' );
				break;
			case 'remove_enrolment':
				setAction( 'remove' );
				break;
			case 'remove_progress':
				setAction( 'reset-progress' );
				break;
			default:
		}
	};

	const openModal = () => {
		const hiddenSenseiBulkAction = document.getElementById(
			'bulk-action-selector-top'
		);

		const hiddenSelectedUserIdsField = document.getElementById(
			'bulk-action-user-ids'
		);

		if ( hiddenSenseiBulkAction ) {
			setActionValue( hiddenSenseiBulkAction.value );
		}

		if ( hiddenSelectedUserIdsField ) {
			try {
				const parsedStudentIds = JSON.parse(
					hiddenSelectedUserIdsField.value
				);
				setStudentIds( parsedStudentIds );
				if ( parsedStudentIds.length === 1 ) {
					setStudentName(
						document
							.querySelector(
								'input.sensei_user_select_id:checked'
							)
							.closest( 'tr' )
							.querySelector( '.student-action-menu' )
							.getAttribute( 'data-user-display-name' )
					);
				}
			} catch ( e ) {}
		}

		setIsModalOpen( true );
	};
	return (
		<>
			<Button
				className="button button-primary sensei-student-bulk-actions__button"
				id="sensei-bulk-learner-actions-modal-toggle"
				onClick={ openModal }
			>
				{ __( 'Select Action', 'sensei-lms' ) }
			</Button>
			<input type="hidden" id="bulk-action-user-ids" />
			{ isModalOpen && (
				<StudentModal
					action={ action }
					onClose={ closeModal }
					students={ studentIds }
					studentDisplayName={ studentName }
				/>
			) }
		</>
	);
};

Array.from(
	document.getElementsByClassName( 'sensei-student-bulk-actions__button' )
).forEach( ( button ) => {
	render( <StudentBulkActionButton />, button );
} );
