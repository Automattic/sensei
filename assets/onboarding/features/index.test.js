import { render, fireEvent } from '@testing-library/react';

import QueryStringRouter from '../query-string-router';
import Features from './index';

// TODO: Needs more test after the integration
describe( '<Features />', () => {
	it( 'Should check the first checkbox', () => {
		const { container } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		fireEvent.change( container.querySelector( 'input[type="checkbox"]' ), {
			target: { checked: true },
		} );

		expect( container.querySelectorAll( 'input:checked' ).length ).toEqual(
			1
		);
	} );
} );
