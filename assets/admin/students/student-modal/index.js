/**
 * WordPress dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseList from './course-list';
import InputControl from '../../../blocks/editor-components/input-control';

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {Object}   props.action  Action that is being performed.
 * @param {Function} props.onClose Close callback.
 */
const StudentModal = ( { action, onClose } ) => {
	const addDescription = __(
		'Select the course(s) you would like to add students to:',
		'sensei-lms'
	);

	const removeDescription = __(
		'Select the course(s) you would like to remove students from:',
		'sensei-lms'
	);

	const addButton = (
		<Button isPrimary className="sensei-student-modal__action--add">
			{ __( 'Add to Course', 'sensei-lms' ) }
		</Button>
	);

	const removeButton = (
		<Button className="sensei-student-modal__action--remove">
			{ __( 'Remove from Course', 'sensei-lms' ) }
		</Button>
	);

	return (
		<Modal
			className="sensei-student-modal"
			title={ __( 'Choose Course', 'sensei-lms' ) }
			onRequestClose={ onClose }
		>
			<p>{ 'add' === action ? addDescription : removeDescription }</p>
			<InputControl
				placeholder={ __( 'Search courses', 'sensei-lms' ) }
				iconRight={ search }
			/>
			<CourseList />
			<div className="sensei-student-modal__action">
				{ 'add' === action ? addButton : removeButton }
			</div>
		</Modal>
	);
};

export default StudentModal;
