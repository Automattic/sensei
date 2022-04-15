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

const AddButton = (
	<Button className="sensei-student-modal__action--add" variant="primary">
		{ __( 'Add to Course', 'sensei-lms' ) }
	</Button>
);

const RemoveButton = (
	<Button className="sensei-student-modal__action--remove">
		{ __( 'Remove from Course', 'sensei-lms' ) }
	</Button>
);

const ResetButton = (
	<Button className="sensei-student-modal__action--remove">
		{ __( 'Reset or Remove the student(s) progress', 'sensei-lms' ) }
	</Button>
);

const POSSIBLE_ACTIONS = {
	add: {
		description: __(
			'Select the course(s) you would like to add students to:',
			'sensei-lms'
		),
		button: AddButton,
	},
	remove: {
		description: __(
			'Select the course(s) you would like to remove students from:',
			'sensei-lms'
		),
		button: RemoveButton,
	},
	'reset-progress': {
		description: __(
			'Select the course(s) you would like to reset the students progress:',
			'sensei-lms'
		),
		button: ResetButton,
	},
};

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {Object}   props.action  Action that is being performed.
 * @param {Function} props.onClose Close callback.
 */
export const StudentModal = ( { action, onClose } ) => {
	const { description, button } = POSSIBLE_ACTIONS[ action ];
	return (
		<Modal
			className="sensei-student-modal"
			title={ __( 'Choose Course', 'sensei-lms' ) }
			onRequestClose={ onClose }
		>
			<p>{ description }</p>

			<InputControl
				placeholder={ __( 'Search courses', 'sensei-lms' ) }
				iconRight={ search }
			/>
			<CourseList />
			<div className="sensei-student-modal__action">{ button }</div>
		</Modal>
	);
};

export default StudentModal;
