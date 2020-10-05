import { StatusControl, Status } from './index';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

describe( '<StatusControl />', () => {
	it( 'Should display the default options', () => {
		const { getByLabelText } = render(
			<StatusControl
				status={ Status.IN_PROGRESS }
				setStatus={ () => {} }
			/>
		);
		const options = [
			getByLabelText( 'In Progress' ),
			getByLabelText( 'Completed' ),
		];

		const optionValues = options.map( ( option ) => option.value );
		expect( optionValues ).toEqual( [
			Status.IN_PROGRESS,
			Status.COMPLETED,
		] );
	} );

	it( 'Should display the specified options', () => {
		const { getByLabelText } = render(
			<StatusControl
				options={ [ Status.COMPLETED, Status.NOT_STARTED ] }
				status={ Status.IN_PROGRESS }
				setStatus={ () => {} }
			/>
		);
		const options = [
			getByLabelText( 'Not Started' ),
			getByLabelText( 'Completed' ),
		];

		const optionValues = options.map( ( option ) => option.value );
		expect( optionValues ).toEqual( [
			Status.NOT_STARTED,
			Status.COMPLETED,
		] );
	} );

	it( 'Should call the callback with a correct value when an option is clicked.', async () => {
		const setStatusMock = jest.fn();
		const { getByLabelText } = render(
			<StatusControl
				options={ [
					Status.NOT_STARTED,
					Status.IN_PROGRESS,
					Status.COMPLETED,
				] }
				status={ Status.NOT_STARTED }
				setStatus={ setStatusMock }
			/>
		);
		userEvent.click( getByLabelText( 'Completed' ) );
		expect( setStatusMock ).toBeCalledWith( Status.COMPLETED );

		userEvent.click( getByLabelText( 'In Progress' ) );
		expect( setStatusMock ).toBeCalledWith( Status.IN_PROGRESS );
	} );
} );
