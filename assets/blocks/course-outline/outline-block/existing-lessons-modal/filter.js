/**
 * WordPress dependencies
 */
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

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
 * @param {Function} props.setFilters Filters state setter.
 */
const Filter = ( { setFilters } ) => {
	const createFilterChangeHandler = ( filterKey, wait ) =>
		debounce( ( value ) => {
			setFilters( ( prevFilters ) => ( {
				...prevFilters,
				[ filterKey ]: value,
			} ) );
		}, wait );

	return (
		<ul className="sensei-lms-existing-lessons-modal__filters">
			<li>
				<InputControl
					className="sensei-lms-existing-lessons-modal__search-input"
					placeholder={ __( 'Search lessons', 'sensei-lms' ) }
					iconRight={ search }
					onChange={ createFilterChangeHandler( 'search', 400 ) }
				/>
			</li>
		</ul>
	);
};

export default Filter;
