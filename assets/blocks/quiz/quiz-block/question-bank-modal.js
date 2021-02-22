/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
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

const QuestionBankModal = ( { setOpen } ) => {
	const questions = useSelect( ( select ) =>
		select( 'core' ).getEntityRecords( 'postType', 'question', {
			per_page: 100,
		} )
	);

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

			{ questions && (
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
						{ questions.map( ( question ) => (
							<tr key={ question.id }>
								<td className="sensei-lms-quiz-block__questions-modal__question-checkbox">
									<CheckboxControl
										id={ `question-${ question.id }` }
										checked={ false }
										onChange={ () => {} }
									/>
								</td>
								<td className="sensei-lms-quiz-block__questions-modal__question-title">
									<label
										htmlFor={ `question-${ question.id }` }
									>
										{ question.title.rendered }
									</label>
								</td>
								<td>{ question[ 'question-type' ][ 0 ] }</td>
								<td>
									{ question[ 'question-category' ].join(
										', '
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }

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
