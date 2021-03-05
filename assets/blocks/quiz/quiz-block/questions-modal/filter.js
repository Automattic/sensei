/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import InputControl from '../../../editor-components/input-control';
import questionTypesConfig from '../../answer-blocks';
import { useQuestionTypes } from '../use-question-types';

/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * Questions filter.
 *
 * @param {Object}   props
 * @param {Array}    props.questionCategories Question categories.
 * @param {Object}   props.filters            Filters object.
 * @param {Function} props.setFilters         Filters state setter.
 */
const Filter = ( { questionCategories, filters, setFilters } ) => {
	const { searchValue } = useState( filters.search );

	const questionTypes = useQuestionTypes();

	const createFilterChangeHandler = ( filterKey, wait ) =>
		debounce( ( value ) => {
			setFilters( ( prevFilters ) => ( {
				...prevFilters,
				[ filterKey ]: value,
			} ) );
		}, wait );

	const typeOptions = [
		{
			value: '',
			label: __( 'Type', 'sensei-lms' ),
		},
		...( questionTypes || [] ).map( ( questionType ) => ( {
			value: questionType.id,
			label: questionTypesConfig[ questionType.slug ]?.title,
		} ) ),
	];

	const categoryOptions = [
		{
			value: '',
			label: __( 'Category', 'sensei-lms' ),
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
					onChange={ createFilterChangeHandler( 'question-type', 0 ) }
				/>
			</li>
			<li>
				<SelectControl
					options={ categoryOptions }
					value={ filters[ 'question-category' ] }
					onChange={ createFilterChangeHandler(
						'question-category',
						0
					) }
				/>
			</li>
			<li>
				<InputControl
					className="sensei-lms-quiz-block__questions-modal__search-input"
					placeholder={ __( 'Search questions', 'sensei-lms' ) }
					iconRight={ search }
					value={ searchValue }
					onChange={ createFilterChangeHandler( 'search', 400 ) }
				/>
			</li>
		</ul>
	);
};

export default Filter;
