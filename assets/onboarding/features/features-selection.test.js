import { render, fireEvent } from '@testing-library/react';

import FeaturesSelection from './features-selection';

const features = [
	{
		id: 'a',
		title: 'Lorem',
		description: 'Ipsum',
	},
	{
		id: 'b',
		title: 'Lorem',
		description: 'Ipsum',
	},
];

describe( '<FeaturesSelection />', () => {
	it( 'Should render the checkboxes for each feature', () => {
		const selectedIds = [ 'b' ];

		const { container } = render(
			<FeaturesSelection
				features={ features }
				selectedIds={ selectedIds }
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
				selectedIds={ [ 'b' ] }
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
} );
