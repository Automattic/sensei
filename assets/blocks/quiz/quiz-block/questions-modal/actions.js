/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Questions actions.
 *
 * @param {Object}   props
 * @param {number[]} props.selectedQuestionIds    Selected question IDs.
 * @param {Object}   props.setSelectedQuestionIds Selected question IDs state setter.
 * @param {Function} props.addExistingQuestions   Callback to add existing questions.
 * @param {Function} props.setOpen                Set the modal's open status.
 */
const Actions = ( {
	selectedQuestionIds,
	setSelectedQuestionIds,
	addExistingQuestions,
	setOpen,
} ) => {
	const [ isAddingSelected, setIsAddingSelected ] = useState( false );

	const clearSelected = () => {
		setSelectedQuestionIds( [] );
	};

	const addSelected = () => {
		setIsAddingSelected( true );
		addExistingQuestions( selectedQuestionIds, () => {
			setOpen( false );
			setIsAddingSelected( false );
		} );
	};

	const addSelectedLabel =
		selectedQuestionIds.length === 0
			? __( 'Add Selected', 'sensei-lms' )
			: sprintf(
					/* translators: Number of selected questions. */
					__( 'Add Selected (%s)', 'sensei-lms' ),
					selectedQuestionIds.length
			  );

	return (
		<ul className="sensei-lms-quiz-block__questions-modal__actions">
			{ selectedQuestionIds.length > 0 && (
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
