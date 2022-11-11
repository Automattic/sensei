/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NavigationBar from './index';
import { useQueryStringRouter } from '../../shared/query-string-router';

jest.mock( '../../shared/query-string-router' );

describe( '<NavigationBar />', () => {
	it( 'Should render navigation bar properly filled', () => {
		useQueryStringRouter.mockReturnValue( { currentRoute: 'B' } );

		const steps = [
			{ key: 'A' },
			{ key: 'B' },
			{ key: 'C' },
			{ key: 'D' },
		];
		const { getByRole } = render( <NavigationBar steps={ steps } /> );

		expect(
			getByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
		expect( getByRole( 'progressbar' ).style.width ).toEqual( '50%' );
	} );
} );
