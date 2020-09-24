import { StatusControl, Statuses } from './index';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

describe( '<StatusControl />', () => {
	it( 'Should display the default options', () => {
		const { getByTestId } = render(
			<StatusControl
				data-testid={ 'select-test-id' }
				status={ Statuses.IN_PROGRESS }
				setStatus={ () => {} }
			/>
		);
		const select = getByTestId( 'select-test-id' );

		const optionValues = Array.from( select.childNodes ).map(
			( option ) => option.value
		);
		expect( optionValues ).toEqual( [
			Statuses.IN_PROGRESS,
			Statuses.COMPLETED,
		] );
	} );

	it( 'Should display the specified options', () => {
		const { getByTestId } = render(
			<StatusControl
				data-testid={ 'select-test-id' }
				includeStatuses={ [ Statuses.COMPLETED, Statuses.NOT_STARTED ] }
				status={ Statuses.IN_PROGRESS }
				setStatus={ () => {} }
			/>
		);
		const select = getByTestId( 'select-test-id' );

		const optionValues = Array.from( select.childNodes ).map(
			( option ) => option.value
		);
		expect( optionValues ).toEqual( [
			Statuses.COMPLETED,
			Statuses.NOT_STARTED,
		] );
	} );

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
