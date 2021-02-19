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
			title={ __( 'Questions bank', 'sensei-lms' ) }
			onRequestClose={ () => {
				setOpen( false );
			} }
		>
			<SelectControl
				options={ [
					{ value: '', label: 'Type', disabled: true },
					{ value: 'T1', label: 'Type 1' },
					{ value: 'T2', label: 'Type 2' },
				] }
				value=""
				onChange={ () => {} }
			/>
			<SelectControl
				options={ [
					{ value: '', label: 'Category', disabled: true },
					{ value: 'C1', label: 'Category 1' },
					{ value: 'C2', label: 'Category 2' },
				] }
				value=""
				onChange={ () => {} }
			/>
			<InputControl
				placeholder="Search questions"
				value=""
				iconRight={ search }
				onChange={ () => {} }
			/>
			<table>
				<thead>
					<tr>
						<th>
							<CheckboxControl
								checked={ false }
								label={ __( 'Question', 'sensei-lms' ) }
								onChange={ () => {} }
							/>
						</th>
						<th>{ __( 'Type', 'sensei-lms' ) }</th>
						<th>{ __( 'Category', 'sensei-lms' ) }</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<CheckboxControl
								checked={ false }
								label={ __(
									'How do you add a course?',
									'sensei-lms'
								) }
								onChange={ () => {} }
							/>
						</td>
						<td>{ __( 'Multiple choice', 'sensei-lms' ) }</td>
						<td>{ __( 'Sensei LMS', 'sensei-lms' ) }</td>
					</tr>
				</tbody>
			</table>

			<Button isPrimary>Add Selected</Button>
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
