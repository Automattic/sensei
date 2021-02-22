/**
 * WordPress dependencies
 */
import {
	Button,
	CheckboxControl,
	SelectControl,
	Modal,
} from '@wordpress/components';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import InputControl from '../../editor-components/input-control';

const QuestionBankModal = ( { isOpen, setOpen } ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="sensei-lms-quiz-block__questions-modal"
			title={ __( 'Questions', 'sensei-lms' ) }
			onRequestClose={ () => {
				setOpen( false );
			} }
		>
			<ul className="sensei-lms-quiz-block__questions-modal__filters">
				<li>
					<SelectControl
						options={ [
							{ value: '', label: 'Type', disabled: true },
							{ value: 'T1', label: 'Type 1' },
							{ value: 'T2', label: 'Type 2' },
						] }
						value=""
						onChange={ () => {} }
					/>
				</li>
				<li>
					<SelectControl
						options={ [
							{ value: '', label: 'Category', disabled: true },
							{ value: 'C1', label: 'Category 1' },
							{ value: 'C2', label: 'Category 2' },
						] }
						value=""
						onChange={ () => {} }
					/>
				</li>
				<li>
					<InputControl
						className="sensei-lms-quiz-block__questions-modal__search-input"
						placeholder="Search questions"
						value=""
						iconRight={ search }
						onChange={ () => {} }
					/>
				</li>
			</ul>

			<table className="sensei-lms-quiz-block__questions-modal__table">
				<thead>
					<tr>
						<th>
							<CheckboxControl
								title={ __(
									'Toggle all visible questions selection.',
									'sensei-lms'
								) }
								checked={ false }
								onChange={ () => {} }
							/>
						</th>
						<th>{ __( 'Question', 'sensei-lms' ) }</th>
						<th>{ __( 'Type', 'sensei-lms' ) }</th>
						<th>{ __( 'Category', 'sensei-lms' ) }</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td className="sensei-lms-quiz-block__questions-modal__question-checkbox">
							<CheckboxControl
								id="question-1"
								checked={ false }
								onChange={ () => {} }
							/>
						</td>
						<td className="sensei-lms-quiz-block__questions-modal__question-title">
							<label htmlFor="question-1">
								{ __(
									'How do you add a course?',
									'sensei-lms'
								) }
							</label>
						</td>
						<td>{ __( 'Multiple choice', 'sensei-lms' ) }</td>
						<td>{ __( 'Sensei LMS', 'sensei-lms' ) }</td>
					</tr>
				</tbody>
			</table>

			<ul className="sensei-lms-quiz-block__questions-modal__actions">
				<li>
					<Button isTertiary>
						{ __( 'Clear Selected', 'sensei-lms' ) }
					</Button>
				</li>
				<li>
					<Button isPrimary>Add Selected</Button>
				</li>
			</ul>
		</Modal>
	);
};

QuestionBankModal.Opener = ( { setOpen } ) => (
	<div className="sensei-lms-quiz-block__questions-modal-opener">
		<Button
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
