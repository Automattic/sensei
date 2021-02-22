/**
 * External dependencies
 */
import { keyBy } from 'lodash';

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
import questionTypesConfig from '../answer-blocks';

const getQuestionTypeLabelBySlug = ( slug ) =>
	questionTypesConfig[ slug ]?.title;

const QuestionsModal = ( { setOpen } ) => {
	const { questions, questionTypes, questionCategories } = useSelect(
		( select ) => {
			const { getEntityRecords } = select( 'core' );

			return {
				questions: getEntityRecords( 'postType', 'question', {
					per_page: 100,
				} ),
				questionTypes: getEntityRecords( 'taxonomy', 'question-type', {
					per_page: -1,
				} ),
				questionCategories: getEntityRecords(
					'taxonomy',
					'question-category',
					{
						per_page: -1,
					}
				),
			};
		}
	);

	const questionCategoriesById = keyBy( questionCategories, 'id' );

	return (
		<Modal
			className="sensei-lms-quiz-block__questions-modal"
			title={ __( 'Questions', 'sensei-lms' ) }
			onRequestClose={ () => {
				setOpen( false );
			} }
		>
			{ questions && questionTypes && questionCategories && (
				<>
					<ul className="sensei-lms-quiz-block__questions-modal__filters">
						<li>
							<SelectControl
								options={ [
									{
										value: '',
										label: 'Type',
										disabled: true,
									},
									...questionTypes.map(
										( questionType ) => ( {
											value: questionType.id,
											label: getQuestionTypeLabelBySlug(
												questionType.slug
											),
										} )
									),
								] }
								value=""
								onChange={ () => {} }
							/>
						</li>
						<li>
							<SelectControl
								options={ [
									{
										value: '',
										label: 'Category',
										disabled: true,
									},
									...questionCategories.map(
										( questionCategory ) => ( {
											value: questionCategory.id,
											label: questionCategory.name,
										} )
									),
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

					<div className="sensei-lms-quiz-block__questions-modal__questions">
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
										<td>
											{ getQuestionTypeLabelBySlug(
												question[ 'question-type-slug' ]
											) }
										</td>
										<td>
											{ question[ 'question-category' ]
												.map(
													( questionCategoryId ) =>
														questionCategoriesById[
															questionCategoryId
														]?.name
												)
												.join( ', ' ) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>

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
				</>
			) }
		</Modal>
	);
};

QuestionsModal.Opener = ( { setOpen } ) => (
	<div className="sensei-lms-quiz-block__questions-modal-opener">
		<Button
			isPrimary
			isSmall
			onClick={ () => {
				setOpen( ( open ) => ! open );
			} }
		>
			{ __( 'Add existing questions', 'sensei-lms' ) }
		</Button>
	</div>
);

export default QuestionsModal;
