/**
 * WordPress dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const QuestionBankModal = ( { isOpen, setOpen } ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Questions bank', 'sensei-lms' ) }
			onRequestClose={ () => {
				setOpen( false );
			} }
		>
			[CONTENT]
		</Modal>
	);
};

QuestionBankModal.Appender = ( { setOpen } ) => (
	<div className="sensei-lms-quiz-block__appender">
		<Button
			type="button"
			isPrimary
			isSmall
			onClick={ () => {
				setOpen( ( open ) => ! open );
			} }
		>
			{ __( 'Add questions from the bank', 'sensei-lms' ) }
		</Button>
	</div>
);

export default QuestionBankModal;
