import { render, fireEvent } from '@testing-library/react';
import { UploadPage } from './upload-page';

jest.mock( '@wordpress/api-fetch', () => () => Promise.resolve() );
jest.mock( '../upload-level', () =>
	jest.fn().mockImplementation( () => <div></div> )
);

describe( '<UploadPage /> continue button', () => {
	it( "should be enabled when it's ready.", async () => {
		const state = {
			isSubmitting: false,
			errorMsg: null,
		};
		const submitStartImport = jest.fn();
		const isReady = true;
		const jobId = 'test-id';

		const { getByText } = render(
			<UploadPage
				jobId={ jobId }
				state={ state }
				isReady={ isReady }
				submitStartImport={ submitStartImport }
				throwEarlyUploadError={ jest.fn() }
				uploadFileForLevel={ jest.fn() }
			/>
		);

		fireEvent.click( getByText( 'Continue' ) );
		expect( submitStartImport ).toHaveBeenCalledTimes( 1 );
	} );

	it( "should be disabled when it's not ready.", () => {
		const state = {
			isSubmitting: false,
			errorMsg: null,
		};
		const submitStartImport = jest.fn();
		const isReady = false;
		const jobId = 'test-id';

		const { getByText } = render(
			<UploadPage
				jobId={ jobId }
				state={ state }
				isReady={ isReady }
				submitStartImport={ submitStartImport }
				throwEarlyUploadError={ jest.fn() }
				uploadFileForLevel={ jest.fn() }
			/>
		);

		fireEvent.click( getByText( 'Continue' ) );
		expect( submitStartImport ).toHaveBeenCalledTimes( 0 );
	} );

	it( "should be disabled when it's already being submitted.", () => {
		const state = {
			isSubmitting: true,
			errorMsg: null,
		};
		const submitStartImport = jest.fn();
		const isReady = true;

		const { getByText } = render(
			<UploadPage
				state={ state }
				isReady={ isReady }
				submitStartImport={ submitStartImport }
				throwEarlyUploadError={ jest.fn() }
				uploadFileForLevel={ jest.fn() }
			/>
		);

		fireEvent.click( getByText( 'Continue' ) );
		expect( submitStartImport ).toHaveBeenCalledTimes( 0 );
	} );
} );
