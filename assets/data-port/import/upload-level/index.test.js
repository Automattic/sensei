import { render } from '@testing-library/react';
import { UploadLevels } from './index';

describe( '<UploadLevels />', () => {
	it( 'should not be ready when no file is uploaded', async () => {
		const initialLevels = [
			{
				key: 'courses',
				description: 'Courses CSV File',
				isUploaded: false,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			},
			{
				key: 'lessons',
				description: 'Lessons CSV File',
				isUploaded: false,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			},
		];

		let readyStatus = null;

		const { queryAllByText } = render(
			<UploadLevels
				setReadyStatus={ ( status ) => {
					readyStatus = status;
				} }
				initialState={ initialLevels }
			/>
		);

		expect( readyStatus ).toBe( false );

		const uploadButtons = await queryAllByText( 'Upload' );
		expect( uploadButtons ).toHaveLength( 2 );
	} );

	it( 'should be ready when a file is uploaded', async () => {
		const initialLevels = [
			{
				key: 'courses',
				description: 'Courses CSV File',
				isUploaded: true,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			{
				key: 'lessons',
				description: 'Lessons CSV File',
				isUploaded: false,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			},
		];

		let readyStatus = null;

		const { queryAllByText } = render(
			<UploadLevels
				setReadyStatus={ ( status ) => {
					readyStatus = status;
				} }
				initialState={ initialLevels }
			/>
		);

		expect( readyStatus ).toBe( true );

		const uploadButtons = await queryAllByText( 'Upload' );
		expect( uploadButtons ).toHaveLength( 2 );

		const files = await queryAllByText( 'coursesfile.csv' );
		expect( files ).toHaveLength( 1 );
	} );

	it( 'should not be ready when a file is uploading', async () => {
		const initialLevels = [
			{
				key: 'courses',
				description: 'Courses CSV File',
				isUploaded: true,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			{
				key: 'lessons',
				description: 'Lessons CSV File',
				isUploaded: true,
				inProgress: true,
				hasError: false,
				errorMsg: null,
				filename: 'lessonsfile.csv',
			},
		];

		let readyStatus = null;

		const { queryAllByText } = render(
			<UploadLevels
				setReadyStatus={ ( status ) => {
					readyStatus = status;
				} }
				initialState={ initialLevels }
			/>
		);

		expect( readyStatus ).toBe( false );

		const uploadButtons = await queryAllByText( 'Upload' );
		expect( uploadButtons ).toHaveLength( 1 );

		const uploadingButtons = await queryAllByText( 'Uploading…' );
		expect( uploadingButtons ).toHaveLength( 1 );

		const files = await queryAllByText( 'lessonsfile.csv' );
		expect( files ).toHaveLength( 1 );
	} );

	it( 'should display the error when there is one', async () => {
		const initialLevels = [
			{
				key: 'courses',
				description: 'Courses CSV File',
				isUploaded: true,
				inProgress: false,
				hasError: false,
				errorMsg: null,
				filename: 'coursesfile.csv',
			},
			{
				key: 'lessons',
				description: 'Lessons CSV File',
				isUploaded: false,
				inProgress: false,
				hasError: true,
				errorMsg: 'The error',
				filename: 'lessonsfile.csv',
			},
		];

		let readyStatus = null;

		const { queryAllByText } = render(
			<UploadLevels
				setReadyStatus={ ( status ) => {
					readyStatus = status;
				} }
				initialState={ initialLevels }
			/>
		);

		expect( readyStatus ).toBe( true );

		const uploadButtons = await queryAllByText( 'Upload' );
		expect( uploadButtons ).toHaveLength( 2 );

		const error = await queryAllByText( 'The error' );
		expect( error ).toHaveLength( 1 );
	} );
} );
