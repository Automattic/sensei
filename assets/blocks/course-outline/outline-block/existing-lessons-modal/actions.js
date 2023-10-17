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
 * @param {number[]} props.selectedLessonIds      Selected lesson IDs.
 * @param {Object}   props.setSelectedLessonIds   Selected lesson IDs state setter.
 * @param {Function} props.onAdd                  Callback to add existing lessons.
 * @param {Function} props.closeModal             Close the modal.
 * @param {Function} props.setErrorAddingSelected Set when there has been an error adding selection.
 */
const Actions = ( {
	selectedLessonIds,
	setSelectedLessonIds,
	onAdd,
	closeModal,
	setErrorAddingSelected,
} ) => {
	const [ isAddingSelected, setIsAddingSelected ] = useState( false );

	const clearSelected = () => {
		setSelectedLessonIds( [] );
	};

	const addSelected = () => {
		setIsAddingSelected( true );
		onAdd( selectedLessonIds )
			.then( closeModal )
			.catch( () => {
				setErrorAddingSelected( true );
				setIsAddingSelected( false );
			} );
	};

	const addSelectedLabel =
		selectedLessonIds.length === 0
			? __( 'Add Selected', 'sensei-lms' )
			: sprintf(
					/* translators: Number of selected lessons. */
					__( 'Add Selected (%s)', 'sensei-lms' ),
					selectedLessonIds.length
			  );

	return (
		<ul className="sensei-lms-quiz-block__existing-lessons-modal__actions">
			{ selectedLessonIds.length > 0 && (
				<li>
					<Button isTertiary onClick={ clearSelected }>
						{ __( 'Clear Selected', 'sensei-lms' ) }
					</Button>
				</li>
			) }
			<li>
				<Button
					disabled={ isAddingSelected }
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
