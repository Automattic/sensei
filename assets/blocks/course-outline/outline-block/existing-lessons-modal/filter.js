/**
 * WordPress dependencies
 */
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import InputControl from '../../../editor-components/input-control';

/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * Lessons filter.
 *
 * @param {Object}   props
 * @param {Object}   props.filters    Filters object.
 * @param {Function} props.setFilters Filters state setter.
 */
const Filter = ( { filters, setFilters } ) => {
	const { searchValue } = useState( filters.search );

	const createFilterChangeHandler = ( filterKey, wait ) =>
		debounce( ( value ) => {
			setFilters( ( prevFilters ) => ( {
				...prevFilters,
				[ filterKey ]: value,
			} ) );
		}, wait );

	return (
		<ul className="wp-block-sensei-lms-course-outline__existing-lessons-modal__filters">
			<li>
				<InputControl
					className="wp-block-sensei-lms-course-outline__existing-lessons-modal__search-input"
					placeholder={ __( 'Search lessons', 'sensei-lms' ) }
					iconRight={ search }
					value={ searchValue }
					onChange={ createFilterChangeHandler( 'search', 400 ) }
				/>
			</li>
		</ul>
	);
};

export default Filter;
