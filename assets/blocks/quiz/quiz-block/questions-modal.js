/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
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

const QuestionsModalContent = ( { setOpen } ) => {
	const [ filters, setFilters ] = useState( {
		search: '',
		'question-type': '',
		'question-category': '',
	} );

	const createChangeHandler = ( filterKey ) => ( value ) => {
		setFilters( ( prevFilters ) => ( {
			...prevFilters,
			[ filterKey ]: value,
		} ) );
	};

	const questions = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'postType', 'question', {
				per_page: 100,
				...filters,
			} ),
		[ filters ]
	);
	const { questionTypes, questionCategories } = useSelect( ( select ) => {
		const { getEntityRecords } = select( 'core' );

		return {
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
	} );

	const questionCategoriesById = keyBy( questionCategories, 'id' );

	return (
		<Modal
			className="sensei-lms-quiz-block__questions-modal"
			title={ __( 'Questions', 'sensei-lms' ) }
			onRequestClose={ () => {
				setOpen( false );
			} }
		>
			{ questionTypes && questionCategories && (
				<>
					<ul className="sensei-lms-quiz-block__questions-modal__filters">
						<li>
							<SelectControl
								options={ [
									{
										value: '',
										label: 'Type',
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
								value={ filters[ 'question-type' ] }
								onChange={ createChangeHandler(
									'question-type'
								) }
							/>
						</li>
						<li>
							<SelectControl
								options={ [
									{
										value: '',
										label: 'Category',
									},
									...questionCategories.map(
										( questionCategory ) => ( {
											value: questionCategory.id,
											label: questionCategory.name,
										} )
									),
								] }
								value={ filters[ 'question-category' ] }
								onChange={ createChangeHandler(
									'question-category'
								) }
							/>
						</li>
						<li>
							<InputControl
								className="sensei-lms-quiz-block__questions-modal__search-input"
								placeholder="Search questions"
								iconRight={ search }
								value={ filters.search }
								onChange={ createChangeHandler( 'search' ) }
							/>
						</li>
					</ul>

					<div className="sensei-lms-quiz-block__questions-modal__questions">
						{ questions && questions.length === 0 && (
							<p>{ __( 'No questions found.', 'sensei-lms' ) }</p>
						) }
						{ questions && questions.length > 0 && (
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
										<th>
											{ __( 'Question', 'sensei-lms' ) }
										</th>
										<th>{ __( 'Type', 'sensei-lms' ) }</th>
										<th>
											{ __( 'Category', 'sensei-lms' ) }
										</th>
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
													question[
														'question-type-slug'
													]
												) }
											</td>
											<td>
												{ question[
													'question-category'
												]
													.map(
														(
															questionCategoryId
														) =>
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
						) }
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

const QuestionsModal = ( { children } ) => {
	const [ isOpen, setOpen ] = useState( false );

	return (
		<>
			<div className="sensei-lms-quiz-block__questions-modal-opener">
				<Button
					isPrimary
					isSmall
					onClick={ () => {
						setOpen( ( open ) => ! open );
					} }
				>
					{ children }
				</Button>
			</div>

			{ isOpen && <QuestionsModalContent setOpen={ setOpen } /> }
		</>
	);
};

export default QuestionsModal;
