/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProgressBar from './index';
import { useQueryStringRouter } from '../../shared/query-string-router';

jest.mock( '../../shared/query-string-router' );

describe( '<ProgressBar />', () => {
	it( 'Should render progress bar properly filled', () => {
		useQueryStringRouter.mockReturnValue( { currentRoute: 'B' } );

		const steps = [
			{ key: 'A' },
			{ key: 'B' },
			{ key: 'C' },
			{ key: 'D' },
		];
		const { getByRole } = render( <ProgressBar steps={ steps } /> );

		expect(
			getByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
		expect( getByRole( 'progressbar' ).style.width ).toEqual( '50%' );
	} );
} );
