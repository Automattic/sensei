/**
 * External dependencies
 */
import { keyBy, uniq, omitBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import questionTypesConfig from '../../answer-blocks';

/**
 * Questions for selection.
 *
 * @param {Object}   props
 * @param {string}   props.clientId               Quiz block ID.
 * @param {Array}    props.questionCategories     Question categories.
 * @param {Object}   props.filters                Filters object.
 * @param {number[]} props.selectedQuestionIds    Seleted question IDs.
 * @param {Object}   props.setSelectedQuestionIds Seleted question IDs state setter.
 */
const Questions = ( {
	clientId,
	questionCategories,
	filters,
	selectedQuestionIds,
	setSelectedQuestionIds,
} ) => {
	// Ids of the already added questions.
	const addedQuestionIds = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	).map( ( block ) => block.attributes?.id );

	// Questions by current filter.
	let questions = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'postType', 'question', {
				per_page: 100,
				...omitBy( filters, ( v ) => v === '' ),
			} ),
		[ filters ]
	);

	if ( ! questions || ! questionCategories ) {
		return (
			<div className="sensei-lms-quiz-block__questions-modal__questions sensei-lms-quiz-block__questions-modal__questions--loading">
				<Spinner />
			</div>
		);
	}

	// Filter out already added questions.
	questions = questions.filter(
		( question ) => ! addedQuestionIds.includes( question.id )
	);

	const questionCategoriesById = keyBy( questionCategories, 'id' );

	const allChecked =
		questions.length > 0 &&
		questions.every( ( question ) =>
			selectedQuestionIds.includes( question.id )
		);

	const toggleAllHandler = ( checked ) => {
		const questionIds = questions.map( ( question ) => question.id );

		setSelectedQuestionIds( ( prev ) =>
			checked
				? uniq( [ ...prev, ...questionIds ] )
				: prev.filter(
						( question ) => ! questionIds.includes( question )
				  )
		);
	};

	const toggleQuestion = ( questionId ) => ( checked ) => {
		if ( checked ) {
			setSelectedQuestionIds( ( prev ) => [ ...prev, questionId ] );
		} else {
			setSelectedQuestionIds( ( prev ) =>
				prev.filter( ( id ) => id !== questionId )
			);
		}
	};

	const questionsMap = ( question ) => {
		const type =
			questionTypesConfig[ question[ 'question-type-slug' ] ]?.title;

		const categories = question[ 'question-category' ]
			.map(
				( questionCategoryId ) =>
					questionCategoriesById[ questionCategoryId ]?.name
			)
			.join( ', ' );

		const questionId = question.id;
		const title = question.title.raw;

		return (
			<tr key={ question.id }>
				<td>
					<CheckboxControl
						id={ `question-${ questionId }` }
						title={ title }
						checked={ selectedQuestionIds.includes( questionId ) }
						onChange={ toggleQuestion( questionId ) }
					/>
				</td>
				<td className="sensei-lms-quiz-block__questions-modal__question-title">
					<label
						htmlFor={ `question-${ questionId }` }
						title={ title }
					>
						{ title }
					</label>
				</td>
				<td>{ type }</td>
				<td>{ categories }</td>
			</tr>
		);
	};

	return (
		<div className="sensei-lms-quiz-block__questions-modal__questions">
			<table className="sensei-lms-quiz-block__questions-modal__table">
				<thead>
					<tr>
						<th className="sensei-lms-quiz-block__questions-modal__question-checkbox">
							<CheckboxControl
								title={ __(
									'Toggle all visible questions selection.',
									'sensei-lms'
								) }
								checked={ allChecked }
								onChange={ toggleAllHandler }
							/>
						</th>
						<th>{ __( 'Question', 'sensei-lms' ) }</th>
						<th>{ __( 'Type', 'sensei-lms' ) }</th>
						<th>{ __( 'Category', 'sensei-lms' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ questions.length === 0 ? (
						<tr>
							<td colSpan="4">
								<p>
									{ __(
										'No questions found.',
										'sensei-lms'
									) }
								</p>
							</td>
						</tr>
					) : (
						questions.map( questionsMap )
					) }
				</tbody>
			</table>
		</div>
	);
};

export default Questions;
