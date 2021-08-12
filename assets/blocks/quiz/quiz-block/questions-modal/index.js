/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Notice, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Filter from './filter';
import Questions from './questions';
import Actions from './actions';
import { useQuestionCategories } from '../../question-categories';

/**
 * Internal dependencies
 */
import { useAddExistingQuestions } from '../use-add-existing-questions';

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {string}   props.clientId Quiz block ID.
 * @param {Function} props.onClose  Close callback.
 */
const QuestionsModal = ( { clientId, onClose } ) => {
	const addExistingQuestions = useAddExistingQuestions( clientId );

	const [ filters, setFilters ] = useState( {
		search: '',
		'question-type': '',
		'question-category': '',
	} );

	const [ errorAddingSelected, setErrorAddingSelected ] = useState( false );
	const [ selectedQuestionIds, setSelectedQuestionIds ] = useState( [] );

	const [ questionCategories ] = useQuestionCategories();

	return (
		<Modal
			className="sensei-lms-quiz-block__questions-modal"
			title={ __( 'Questions', 'sensei-lms' ) }
			onRequestClose={ onClose }
		>
			{ errorAddingSelected && (
				<Notice
					status="error"
					isDismissible={ false }
					className="sensei-lms-quiz-block__questions-modal__notice"
				>
					{ __(
						'Unable to add the selected question(s). Please make sure you are still logged in and try again.',
						'sensei-lms'
					) }
				</Notice>
			) }
			<Filter
				questionCategories={ questionCategories }
				filters={ filters }
				setFilters={ setFilters }
			/>
			<Questions
				clientId={ clientId }
				questionCategories={ questionCategories }
				filters={ filters }
				selectedQuestionIds={ selectedQuestionIds }
				setSelectedQuestionIds={ setSelectedQuestionIds }
			/>
			<Actions
				selectedQuestionIds={ selectedQuestionIds }
				setSelectedQuestionIds={ setSelectedQuestionIds }
				onAdd={ addExistingQuestions }
				closeModal={ onClose }
				setErrorAddingSelected={ setErrorAddingSelected }
			/>
		</Modal>
	);
};

export default QuestionsModal;
