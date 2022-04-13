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
 * Student bulk action button.
 */
export const StudentsBulkActionButton = () => {
	const [ action, setAction ] = useState( 'add' );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const closeModal = () => setIsModalOpen( false );
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
		if ( hiddenSenseiBulkAction ) {
			setActionValue( hiddenSenseiBulkAction.value );
		}
		setIsModalOpen( true );
	};
	return (
		<>
			<Button
				className="button button-primary"
				id="sensei-bulk-learner-actions-modal-toggle"
				onClick={ openModal }
				style={ { height: 30 } }
			>
				{ __( 'Select Courses', 'sensei-lms' ) }
			</Button>
			{ isModalOpen && (
				<StudentModal action={ action } onClose={ closeModal } />
			) }
		</>
	);
};

Array.from(
	document.getElementsByClassName( 'student-bulk-action-button' )
).forEach( ( button ) => {
	render( <StudentsBulkActionButton />, button );
} );
