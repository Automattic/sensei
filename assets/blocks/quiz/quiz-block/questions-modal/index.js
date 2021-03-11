/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Notice, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Filter from './filter';
import Questions from './questions';
import Actions from './actions';

/**
 * External dependencies
 */
import { unescape } from 'lodash';

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {Function} props.onClose  Close callback
 * @param {Function} props.onSelect Callback to add selected questions.
 */
const QuestionsModal = ( { onClose, onSelect } ) => {
	const [ filters, setFilters ] = useState( {
		search: '',
		'question-type': '',
		'question-category': '',
	} );

	const [ errorAddingSelected, setErrorAddingSelected ] = useState( false );
	const [ selectedQuestionIds, setSelectedQuestionIds ] = useState( [] );

	const questionCategories = useSelect( ( select ) => {
		const terms = select( 'core' ).getEntityRecords(
			'taxonomy',
			'question-category',
			{
				per_page: -1,
			}
		);

		if ( terms && terms.length ) {
			return terms.map( ( term ) => ( {
				...term,
				name: unescape( term.name ),
			} ) );
		}

		return terms;
	} );

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
				questionCategories={ questionCategories }
				filters={ filters }
				selectedQuestionIds={ selectedQuestionIds }
				setSelectedQuestionIds={ setSelectedQuestionIds }
			/>
			<Actions
				selectedQuestionIds={ selectedQuestionIds }
				setSelectedQuestionIds={ setSelectedQuestionIds }
				onAdd={ onSelect }
				closeModal={ onClose }
				setErrorAddingSelected={ setErrorAddingSelected }
			/>
		</Modal>
	);
};

export default QuestionsModal;
