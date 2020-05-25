import { render, fireEvent } from '@testing-library/react';

import FeaturesSelection from './features-selection';

const features = [
	{
		slug: 'empty-status',
		title: 'Lorem',
		excerpt: 'Ipsum',
	},
	{
		slug: 'installing',
		title: 'Lorem',
		excerpt: 'Ipsum',
		status: 'installing',
	},
	{
		slug: 'installed',
		title: 'Lorem',
		excerpt: 'Ipsum',
		status: 'installed',
	},
];

describe( '<FeaturesSelection />', () => {
	it( 'Should render the checkboxes for each feature', () => {
		const selectedSlugs = [ 'installing' ];

		const { container } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ selectedSlugs }
				onChange={ () => {} }
				onContinue={ () => {} }
			/>
		);

		expect( container.querySelectorAll( 'input' ).length ).toEqual(
			features.length
		);
		expect( container.querySelectorAll( 'input:checked' ).length ).toEqual(
			selectedSlugs.length
		);
	} );

	it( 'Should call the callbacks correctly', () => {
		const onChangeMock = jest.fn();
		const onContinueMock = jest.fn();

		const { container, queryByText } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ [ 'installing' ] }
				onChange={ onChangeMock }
				onContinue={ onContinueMock }
			/>
		);

		fireEvent.click(
			container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ]
		);
		expect( onChangeMock ).toBeCalledWith( [
			'empty-status',
			'installing',
		] );

		fireEvent.click(
			container.querySelectorAll( 'input[type="checkbox"]' )[ 1 ]
		);
		expect( onChangeMock ).toBeCalledWith( [] );

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should render the features with installation status as disabled and the installed with specific class', () => {
		const selectedSlugs = [ 'empty-status', 'installing', 'installed' ];
		const { container } = render(
			<FeaturesSelection
				features={ features }
				selectedSlugs={ selectedSlugs }
				onChange={ () => {} }
				onContinue={ () => {} }
			/>
		);
		expect(
			container.querySelectorAll( 'input:checked:disabled' ).length
		).toEqual( 2 );
		expect(
			container.querySelectorAll( '.installed-status' ).length
		).toEqual( 1 );
	} );
} );
