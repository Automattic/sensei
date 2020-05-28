import { render, fireEvent } from '@testing-library/react';
import { UploadPage } from './index';
import { moveToNextAction } from '../../stepper';

let mockReadyStatus;

jest.mock( '../upload-level', () => ( {
	UploadLevels: ( { setReadyStatus } ) => {
		setReadyStatus( mockReadyStatus );
		return <div>Mock levels</div>;
	},
} ) );

describe( '<UploadPage /> continue button', () => {
	it( "should be enabled when it's ready.", () => {
		let dispatchedAction = null;

		mockReadyStatus = true;

		const { getByText } = render(
			<UploadPage
				importerDispatch={ ( action ) => {
					dispatchedAction = action;
				} }
			/>
		);

		fireEvent.click( getByText( 'Continue' ) );

		expect( dispatchedAction ).toEqual( moveToNextAction() );
	} );

	it( "should be disabled when it's not ready.", () => {
		let dispatchedAction = null;

		mockReadyStatus = false;

		const { getByText } = render(
			<UploadPage
				importerDispatch={ ( action ) => {
					dispatchedAction = action;
				} }
			/>
		);

		fireEvent.click( getByText( 'Continue' ) );

		expect( dispatchedAction ).toBeNull();
	} );
} );
