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
import Lessons from './lessons';
import Actions from './actions';
import { useAddExistingLessons } from './use-add-existing-lessons';

/**
 * Lessons modal content.
 *
 * @param {Object}   props
 * @param {string}   props.clientId Outline block ID.
 * @param {Function} props.onClose  Close callback.
 */
const LessonsModal = ( { clientId, onClose } ) => {
	const addExistingLessons = useAddExistingLessons( clientId );

	const [ filters, setFilters ] = useState( {
		search: '',
	} );

	const [ errorAddingSelected, setErrorAddingSelected ] = useState( false );
	const [ selectedLessonIds, setSelectedLessonIds ] = useState( [] );

	return (
		<Modal
			className="wp-block-sensei-lms-course-outline__existing-lessons-modal"
			title={ __( 'Lessons', 'sensei-lms' ) }
			onRequestClose={ onClose }
		>
			{ errorAddingSelected && (
				<Notice
					status="error"
					isDismissible={ false }
					className="wp-block-sensei-lms-course-outline__existing-lessons-modal__notice"
				>
					{ __(
						'Unable to add the selected lesson(s). Please make sure you are still logged in and try again.',
						'sensei-lms'
					) }
				</Notice>
			) }
			<Filter filters={ filters } setFilters={ setFilters } />
			<Lessons
				clientId={ clientId }
				filters={ filters }
				selectedLessonIds={ selectedLessonIds }
				setSelectedLessonIds={ setSelectedLessonIds }
			/>
			<Actions
				selectedLessonIds={ selectedLessonIds }
				setSelectedLessonIds={ setSelectedLessonIds }
				onAdd={ addExistingLessons }
				closeModal={ onClose }
				setErrorAddingSelected={ setErrorAddingSelected }
			/>
		</Modal>
	);
};

export default LessonsModal;
