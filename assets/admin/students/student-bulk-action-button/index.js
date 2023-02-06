/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { render, useEffect, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StudentModal from '../student-modal';

/**
 *  Student bulk action button.
 *
 * @param {Object}  props
 * @param {boolean} props.isDisabled Set button's initial state to be disabled or enabled, defaults to disabled.
 */
export const StudentBulkActionButton = ( { isDisabled = true } ) => {
	const [ action, setAction ] = useState( 'add' );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ studentIds, setStudentIds ] = useState( [] );
	const [ studentName, setStudentName ] = useState( '' );
	const [ buttonDisabled, setButtonDisabled ] = useState( isDisabled );
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
				setAction( selectedValue );
		}
	};
	const buttonEnableDisableEventHandler = ( args ) => {
		setButtonDisabled( ! ( args.detail && args.detail.enable ) );
	};
	useEffect( () => {
		global.addEventListener(
			'enableDisableCourseSelectionToggle',
			buttonEnableDisableEventHandler
		);
		return () => {
			global.removeEventListener(
				'enableDisableCourseSelectionToggle',
				buttonEnableDisableEventHandler
			);
		};
	}, [] );
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

	const defaultStudentModal = (
		<StudentModal
			action={ action }
			onClose={ closeModal }
			students={ studentIds }
			studentDisplayName={ studentName }
		/>
	);

	/**
	 * Filters the bulk action modal.
	 *
	 * @since 4.11.0
	 *
	 * @param {Object}   modal       The modal component.
	 * @param {string}   action      Selected action.
	 * @param {Function} closeModal  The callback to run when the modal is closed.
	 * @param {Array}    studentIds  Selected student ids.
	 * @param {string}   studentName Selected student name.
	 *
	 * @return {Object} Filtered modal.
	 */
	const modal = applyFilters(
		'senseiStudentBulkActionModal',
		defaultStudentModal,
		action,
		closeModal,
		studentIds,
		studentName
	);

	return (
		<>
			<Button
				className="button button-primary sensei-student-bulk-actions__button"
				disabled={ buttonDisabled }
				id="sensei-bulk-learner-actions-modal-toggle"
				onClick={ openModal }
			>
				{ __( 'Select Action', 'sensei-lms' ) }
			</Button>
			<input type="hidden" id="bulk-action-user-ids" />
			{ isModalOpen && modal }
		</>
	);
};

Array.from(
	document.querySelectorAll( 'div.sensei-student-bulk-actions__button' )
).forEach( ( button ) => {
	render( <StudentBulkActionButton />, button );
} );
