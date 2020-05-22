import { render, fireEvent } from '@testing-library/react';

import FeaturesSelection from './features-selection';

const features = [
	{
		slug: 'a',
		title: 'Lorem',
		excerpt: 'Ipsum',
	},
	{
		slug: 'b',
		title: 'Lorem',
		excerpt: 'Ipsum',
	},
];

describe( '<FeaturesSelection />', () => {
	it( 'Should render the checkboxes for each feature', () => {
		const selectedIds = [ 'b' ];

		const { container } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ selectedIds }
				submittedSlugs={ [] }
				onChange={ () => {} }
				onContinue={ () => {} }
			/>
		);

		expect( container.querySelectorAll( 'input' ).length ).toEqual(
			features.length
		);
		expect( container.querySelectorAll( 'input:checked' ).length ).toEqual(
			selectedIds.length
		);
	} );

	it( 'Should call the callbacks correctly', () => {
		const onChangeMock = jest.fn();
		const onContinueMock = jest.fn();

		const { container, queryByText } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ [ 'b' ] }
				submittedSlugs={ [] }
				onChange={ onChangeMock }
				onContinue={ onContinueMock }
			/>
		);

		fireEvent.click(
			container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ]
		);
		expect( onChangeMock ).toBeCalledWith( [ 'a', 'b' ] );

		fireEvent.click(
			container.querySelectorAll( 'input[type="checkbox"]' )[ 1 ]
		);
		expect( onChangeMock ).toBeCalledWith( [] );

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should render the submitted features as checked and disabled', () => {
		const submittedSlugs = [ 'b' ];

		const { container } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ submittedSlugs }
				submittedSlugs={ submittedSlugs }
				onChange={ () => {} }
				onContinue={ () => {} }
			/>
		);
		expect(
			container.querySelectorAll( 'input:checked:disabled' ).length
		).toEqual( submittedSlugs.length );
	} );
} );
