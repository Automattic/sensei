/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { screen } from '@testing-library/dom';
import lodash from 'lodash';

/**
 * Internal dependencies
 */
import Filter from './filter';

lodash.debounce = jest.fn( ( fn ) => fn );

describe( '<Filter />', () => {
	it( 'Should call setFilters when filter changed', () => {
		const setFilters = jest.fn();

		render( <Filter filters={ {} } setFilters={ setFilters } /> );

		fireEvent.change( screen.getByPlaceholderText( 'Search lessons' ), {
			target: { value: 'Lesson 1' },
		} );

		expect( setFilters ).toHaveBeenCalled();
	} );
} );
