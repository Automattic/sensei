import { LessonStatusControl } from './lesson-status-control';
import { fireEvent, render } from '@testing-library/react';

describe( '<LessonStatusControl />', () => {
	it( 'Should call the callback with a correct value when a button is clicked.', () => {
		const setPreviewStatusMock = jest.fn();
		const { getByText } = render(
			<LessonStatusControl
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
