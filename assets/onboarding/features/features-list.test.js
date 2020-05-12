import { render, fireEvent } from '@testing-library/react';

import FeaturesList, {
	LOADING_STATUS,
	ERROR_STATUS,
	SUCCESS_STATUS,
} from './features-list';

describe( '<FeaturesList />', () => {
	it( 'Should render the list with the content and the custom class', () => {
		const { container, queryByText } = render(
			<FeaturesList className="custom-class">Test</FeaturesList>
		);

		const hasCustomClass = container.firstChild.classList.contains(
			'custom-class'
		);

		expect( queryByText( 'Test' ) ).toBeTruthy();
		expect( hasCustomClass ).toBeTruthy();
	} );

	describe( '<FeaturesList.Item />', () => {
		it( 'Should render correctly', () => {
			const { queryByText } = render(
				<FeaturesList.Item title="Title" description="Description" />
			);

			expect( queryByText( 'Title' ) ).toBeTruthy();
		} );

		it( 'Should with error', () => {
			const onFeatureRetryMock = jest.fn();

			const { queryByText } = render(
				<FeaturesList.Item
					title="Title"
					description="Description"
					errorMessage="Error message"
					onFeatureRetry={ onFeatureRetryMock }
				/>
			);

			expect( queryByText( 'Error message' ) ).toBeTruthy();

			fireEvent.click( queryByText( 'Retry?' ) );
			expect( onFeatureRetryMock ).toBeCalled();
		} );

		describe.each`
			status            | expected
			${LOADING_STATUS} | ${'Loading'}
			${ERROR_STATUS}   | ${'Error'}
			${SUCCESS_STATUS} | ${'Success'}
		`( 'Should render with status $status', ( { status, expected } ) => {
			it( `Render correctly status ${ status }`, () => {
				const { queryByText } = render(
					<FeaturesList.Item
						title="Title"
						description="Description"
						status={ status }
					/>
				);

				expect( queryByText( expected ) ).toBeTruthy();
			} );
		} );
	} );
} );
