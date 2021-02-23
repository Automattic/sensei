/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import InputControl from '../../../editor-components/input-control';
import questionTypesConfig from '../../answer-blocks';

/**
 * Questions filter.
 *
 * @param {Object}   props
 * @param {Array}    props.questionCategories Question categories.
 * @param {Object}   props.filters            Filters object.
 * @param {Function} props.setFilters         Filters state setter.
 */
const Filter = ( { questionCategories, filters, setFilters } ) => {
	const questionTypes = useSelect( ( select ) =>
		select( 'core' ).getEntityRecords( 'taxonomy', 'question-type', {
			per_page: -1,
		} )
	);

	const createFilterChangeHandler = ( filterKey ) => ( value ) => {
		setFilters( ( prevFilters ) => ( {
			...prevFilters,
			[ filterKey ]: value,
		} ) );
	};

	const typeOptions = [
		{
			value: '',
			label: 'Type',
		},
		...( questionTypes || [] ).map( ( questionType ) => ( {
			value: questionType.id,
			label: questionTypesConfig[ questionType.slug ]?.title,
		} ) ),
	];

	const categoryOptions = [
		{
			value: '',
			label: 'Category',
		},
		...( questionCategories || [] ).map( ( questionCategory ) => ( {
			value: questionCategory.id,
			label: questionCategory.name,
		} ) ),
	];

	return (
		<ul className="sensei-lms-quiz-block__questions-modal__filters">
			<li>
				<SelectControl
					options={ typeOptions }
					value={ filters[ 'question-type' ] }
					onChange={ createFilterChangeHandler( 'question-type' ) }
				/>
			</li>
			<li>
				<SelectControl
					options={ categoryOptions }
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
					onChange={ createFilterChangeHandler( 'search' ) }
				/>
			</li>
		</ul>
	);
};

export default Filter;
