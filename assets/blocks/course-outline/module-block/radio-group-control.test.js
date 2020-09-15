import { RadioGroupControl } from './radio-group-control';
import { fireEvent, render } from '@testing-library/react';

describe( '<RadioGroupControl />', () => {
	it( 'Should call the callback with a correct value when a button is clicked.', () => {
		const setPreviewStatusMock = jest.fn();
		const { getByText } = render(
			<RadioGroupControl
				previewStatus={ 'completed' }
				setPreviewStatus={ setPreviewStatusMock }
			/>
		);

		fireEvent.click( getByText( 'In Progress' ) );

		expect( setPreviewStatusMock ).toBeCalledWith( 'in-progress' );

		fireEvent.click( getByText( 'Completed' ) );

		expect( setPreviewStatusMock ).toBeCalledWith( 'completed' );
	} );
} );
