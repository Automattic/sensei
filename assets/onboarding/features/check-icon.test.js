import { render } from '@testing-library/react';

import CheckIcon from './check-icon';

describe( '<CheckIcon />', () => {
	it( 'Should render the icon correctly', () => {
		const { container } = render( <CheckIcon /> );

		expect( container.querySelector( 'path' ) ).toBeTruthy();
	} );
} );
