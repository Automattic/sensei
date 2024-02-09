/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Lessons actions.
 *
 * @param {Object}   props
 * @param {Object[]} props.selectedLessons        Selected lessons.
 * @param {Function} props.setSelectedLessons     Selected lessons state setter.
 * @param {Function} props.onAdd                  Callback to add existing lessons.
 * @param {Function} props.closeModal             Close the modal.
 * @param {Function} props.setErrorAddingSelected Set when there has been an error adding selection.
 */
const Actions = ( {
	selectedLessons,
	setSelectedLessons,
	onAdd,
	closeModal,
	setErrorAddingSelected,
} ) => {
	const [ isAddingSelected, setIsAddingSelected ] = useState( false );

	const clearSelected = () => {
		setSelectedLessons( [] );
	};

	const addSelected = () => {
		setIsAddingSelected( true );
		onAdd( selectedLessons )
			.then( closeModal )
			.catch( () => {
				setErrorAddingSelected( true );
				setIsAddingSelected( false );
			} );
	};

	const addSelectedLabel =
		selectedLessons.length === 0
			? __( 'Add Selected', 'sensei-lms' )
			: sprintf(
					/* translators: Number of selected lessons. */
					__( 'Add Selected (%s)', 'sensei-lms' ),
					selectedLessons.length
			  );

	const isAddButtonDisabled =
		isAddingSelected || selectedLessons.length === 0;

	return (
		<ul className="sensei-lms-existing-lessons-modal__actions">
			{ selectedLessons.length > 0 && (
				<li>
					<Button isTertiary onClick={ clearSelected }>
						{ __( 'Clear Selected', 'sensei-lms' ) }
					</Button>
				</li>
			) }
			<li>
				<Button
					disabled={ isAddButtonDisabled }
					onClick={ addSelected }
					isPrimary
				>
					{ addSelectedLabel }
				</Button>
			</li>
		</ul>
	);
};

export default Actions;
