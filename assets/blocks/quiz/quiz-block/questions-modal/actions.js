/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Questions actions.
 *
 * @param {Object}   props
 * @param {number[]} props.selectedQuestionIds    Seleted question IDs.
 * @param {Object}   props.setSelectedQuestionIds Seleted question IDs state setter.
 */
const Actions = ( { selectedQuestionIds, setSelectedQuestionIds } ) => {
	const clearSelected = () => {
		setSelectedQuestionIds( [] );
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
				<Button isPrimary>{ addSelectedLabel }</Button>
			</li>
		</ul>
	);
};

export default Actions;
