/**
 * External dependencies
 */
import { keyBy, uniq } from 'lodash';

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
import { __, sprintf } from '@wordpress/i18n';

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
	const createFilterChangeHandler = ( filterKey ) => ( value ) => {
		setFilters( ( prevFilters ) => ( {
			...prevFilters,
			[ filterKey ]: value,
		} ) );
	};
	const toggleAllHandler = ( checked ) => {
		const questionIds = questions.map( ( question ) => question.id );
		if ( checked ) {
			setSelectedQuestionIds( ( prev ) =>
				uniq( [ ...prev, ...questionIds ] )
			);
		} else {
			setSelectedQuestionIds( ( prev ) =>
				prev.filter(
					( question ) => ! questionIds.includes( question )
				)
			);
		}
	};

	const [ selectedQuestionIds, setSelectedQuestionIds ] = useState( [] );
	const toggleQuestion = ( questionId ) => ( checked ) => {
		if ( checked ) {
			setSelectedQuestionIds( ( prev ) => [ ...prev, questionId ] );
		} else {
			setSelectedQuestionIds( ( prev ) =>
				prev.filter( ( id ) => id !== questionId )
			);
		}
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
								onChange={ createFilterChangeHandler(
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
								onChange={ createFilterChangeHandler(
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
								onChange={ createFilterChangeHandler(
									'search'
								) }
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
												checked={ questions.every(
													( question ) =>
														selectedQuestionIds.includes(
															question.id
														)
												) }
												onChange={ toggleAllHandler }
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
													checked={ selectedQuestionIds.includes(
														question.id
													) }
													onChange={ toggleQuestion(
														question.id
													) }
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
						{ selectedQuestionIds.length > 0 && (
							<li>
								<Button
									isTertiary
									onClick={ () => {
										setSelectedQuestionIds( [] );
									} }
								>
									{ __( 'Clear Selected', 'sensei-lms' ) }
								</Button>
							</li>
						) }
						<li>
							<Button isPrimary>
								{ selectedQuestionIds.length === 0
									? __( 'Add Selected', 'sensei-lms' )
									: sprintf(
											/* translators: Number of selected questions. */
											__(
												'Add Selected (%s)',
												'sensei-lms'
											),
											selectedQuestionIds.length
									  ) }
							</Button>
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
