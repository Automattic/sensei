import { render } from '@testing-library/react';
import { UploadLevels } from './component';

describe( '<UploadLevels />', () => {
	it( 'should display the error when there is one', () => {
		const levelsState = {
			courses: {
				isUploaded: true,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			lessons: {
				isUploaded: false,
				inProgress: false,
				hasError: true,
				errorMsg: 'The error',
				filename: 'lessonsfile.csv',
			},
			questions: {
				isUploaded: true,
				inProgress: false,
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
} );
