/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Progress from './progress';

jest.mock( '../../shared/query-string-router' );

describe( '<Progress />', () => {
	it( 'Should render progress bar properly filled', () => {
		const { getByRole } = render(
			<Progress totalTasks={ 10 } completedTasks={ 5 } />
		);

		expect(
			getByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
		expect( getByRole( 'progressbar' ).style.width ).toEqual( '50%' );
	} );
} );
