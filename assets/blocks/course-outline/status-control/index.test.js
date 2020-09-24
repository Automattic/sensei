import { StatusControl, Statuses } from './index';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

describe( '<StatusControl />', () => {
	it.todo( 'Should display the options in a dropdown' );

	it.todo( 'Should display the default options' );

	it.todo( 'Should display the specified options' );

	it( 'Should call the callback with a correct value when a button is clicked.', () => {
		const setStatusMock = jest.fn();
		const { getByTestId } = render(
			<StatusControl
				data-testid={ 'select-test-id' }
				status={ Statuses.IN_PROGRESS }
				setStatus={ setStatusMock }
			/>
		);

		const select = getByTestId( 'select-test-id' );

		userEvent.selectOptions( select, [ Statuses.COMPLETED ] );
		expect( setStatusMock ).toBeCalledWith( Statuses.COMPLETED );

		userEvent.selectOptions( select, [ Statuses.IN_PROGRESS ] );
		expect( setStatusMock ).toBeCalledWith( Statuses.IN_PROGRESS );
	} );
} );
