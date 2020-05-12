import { render, fireEvent } from '@testing-library/react';

import FeatureDescription from './feature-description';

describe( '<FeatureDescription />', () => {
	it( 'Should render with the description', () => {
		const { container } = render(
			<FeatureDescription description="test" />
		);

		expect( container.firstChild ).toMatchInlineSnapshot( 'test' );
	} );

	it( 'Should render with learn more link', () => {
		const link = 'https://senseilms.com/';
		const { queryByText } = render(
			<FeatureDescription description="test" learnMoreLink={ link } />
		);

		const href = queryByText( 'Learn more' ).getAttribute( 'href' );

		expect( href ).toEqual( link );
	} );

	it( 'Should render with error', () => {
		const onFeatureRetryMock = jest.fn();

		const { queryByText } = render(
			<FeatureDescription
				description="test"
				errorMessage="Error message"
				onFeatureRetry={ onFeatureRetryMock }
			/>
		);

		expect( queryByText( 'Error message' ) ).toBeTruthy();

		fireEvent.click( queryByText( 'Retry?' ) );
		expect( onFeatureRetryMock ).toBeCalled();
	} );
} );
