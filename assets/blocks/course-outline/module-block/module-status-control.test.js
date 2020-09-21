import { ModuleStatusControl } from './module-status-control';
import { fireEvent, render } from '@testing-library/react';

describe( '<ModuleStatusControl />', () => {
	it( 'Should call the callback with a correct value when a button is clicked.', () => {
		const setPreviewStatusMock = jest.fn();
		const { getByText } = render(
			<ModuleStatusControl
				isPreviewCompleted={ true }
				setIsPreviewCompleted={ setPreviewStatusMock }
			/>
		);

		fireEvent.click( getByText( 'In Progress' ) );

		expect( setPreviewStatusMock ).toBeCalledWith( false );

		fireEvent.click( getByText( 'Completed' ) );

		expect( setPreviewStatusMock ).toBeCalledWith( true );
	} );
} );
