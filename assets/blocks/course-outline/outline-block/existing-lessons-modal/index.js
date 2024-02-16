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
const ExistingLessonsModal = ( { clientId, onClose } ) => {
	const addExistingLessons = useAddExistingLessons( clientId );

	const [ filters, setFilters ] = useState( {
		search: '',
	} );

	const [ errorAddingSelected, setErrorAddingSelected ] = useState( false );
	const [ selectedLessons, setSelectedLessons ] = useState( [] );

	return (
		<Modal
			className="sensei-lms-existing-lessons-modal"
			title={ __( 'Available Lessons', 'sensei-lms' ) }
			onRequestClose={ onClose }
		>
			{ errorAddingSelected && (
				<Notice
					status="error"
					isDismissible={ false }
					className="sensei-lms-existing-lessons-modal__notice"
				>
					{ __(
						'Unable to add the selected lesson(s). Please make sure you are still logged in and try again.',
						'sensei-lms'
					) }
				</Notice>
			) }
			<Filter setFilters={ setFilters } />
			<Lessons
				clientId={ clientId }
				filters={ filters }
				selectedLessons={ selectedLessons }
				setSelectedLessons={ setSelectedLessons }
			/>
			<Actions
				selectedLessons={ selectedLessons }
				setSelectedLessons={ setSelectedLessons }
				onAdd={ addExistingLessons }
				closeModal={ onClose }
				setErrorAddingSelected={ setErrorAddingSelected }
			/>
		</Modal>
	);
};

export default ExistingLessonsModal;
