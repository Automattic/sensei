import { render } from '@testing-library/react';

import FeatureDescription from './feature-description';

describe( '<FeatureDescription />', () => {
	it( 'Should render with the description', () => {
		const { container } = render( <FeatureDescription excerpt="test" /> );

		expect( container.firstChild ).toMatchInlineSnapshot( 'test' );
	} );

	it( 'Should render with learn more link', () => {
		const link = 'https://senseilms.com/';
		const { queryByText } = render(
			<FeatureDescription excerpt="test" link={ link } />
		);

		const href = queryByText( 'Learn more' ).getAttribute( 'href' );

		expect( href ).toEqual( link );
	} );
} );
