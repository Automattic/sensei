import { fireEvent, render } from '@testing-library/react';
import { UploadLevels } from './upload-level';

describe( '<UploadLevels />', () => {
	it( 'should display the error when there is one', () => {
		const levelsState = {
			courses: {
				isUploaded: true,
				isUploading: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			lessons: {
				isUploaded: false,
				isUploading: false,
				isDeleting: false,
				hasError: true,
				errorMsg: 'The error',
				filename: 'lessonsfile.csv',
			},
			questions: {
				isUploaded: true,
				isUploading: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: 'questionsfile.csv',
			},
		};

		const { queryAllByText } = render(
			<UploadLevels
				state={ levelsState }
				uploadFileForLevel={ jest.fn() }
				throwEarlyUploadError={ jest.fn() }
			/>
		);

		const uploadButtons = queryAllByText( 'Upload' );
		expect( uploadButtons ).toHaveLength( 3 );

		const error = queryAllByText( 'The error' );
		expect( error ).toHaveLength( 1 );
	} );

	it( 'should display the delete button when uploaded', () => {
		const levelsState = {
			courses: {
				isUploaded: true,
				isUploading: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			lessons: {
				isUploaded: false,
				isUploading: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			},
			questions: {
				isUploaded: false,
				isUploading: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			},
		};

		const deleteLevelFile = jest.fn();

		const { queryAllByLabelText } = render(
			<UploadLevels
				state={ levelsState }
				uploadFileForLevel={ jest.fn() }
				deleteLevelFile={ deleteLevelFile }
				throwEarlyUploadError={ jest.fn() }
			/>
		);

		const deleteButtons = queryAllByLabelText( 'Delete File' );
		expect( deleteButtons ).toHaveLength( 1 );

		fireEvent.click( deleteButtons[ 0 ] );

		expect( deleteLevelFile ).toBeCalledTimes( 1 );
	} );
} );
