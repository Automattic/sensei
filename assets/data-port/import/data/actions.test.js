import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_IMPORT_DATA,
	SUCCESS_FETCH_IMPORT_DATA,
	ERROR_FETCH_IMPORT_DATA,
	START_START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
} from './constants';

import {
	fetchFromAPI,
	fetchImporterData,
	startFetch,
	successFetch,
	errorFetch,
	submitStartImport,
	startStartImport,
	successStartImport,
	errorStartImport,
	uploadFileForLevel,
	errorFileUpload,
	startFileUploadAction,
	successFileUpload,
	throwEarlyUploadError,
} from './actions';

describe( 'Importer actions', () => {
	/**
	 * API Fetch.
	 */
	it( 'Should return the fetch from API action', () => {
		const requestObject = { path: '/test' };
		const expectedAction = {
			type: FETCH_FROM_API,
			request: requestObject,
		};

		expect( fetchFromAPI( requestObject ) ).toEqual( expectedAction );
	} );

	/**
	 * Fetch importer data action.
	 */
	it( 'Should generate the fetch importer data action', () => {
		const gen = fetchImporterData();

		// Start fetch action.
		const expectedStartFetchAction = {
			type: START_FETCH_IMPORT_DATA,
		};
		expect( gen.next().value ).toEqual( expectedStartFetchAction );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH,
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set data action.
		const dataObject = {
			id: 'test',
			status: {
				status: 'setup',
				percentage: 0,
			},
			files: {
				courses: {
					name: 'courses-sample.csv',
					url:
						'http://example.com/wp-content/uploads/2020/06/b6f91f0d_courses-sample.csv',
				},
			},
		};

		const expectedSetDataAction = {
			data: {
				completedSteps: [],
				id: 'test',
				import: {
					percentage: 0,
					status: 'setup',
				},
				upload: {
					courses: {
						filename: 'courses-sample.csv',
						isUploaded: true,
					},
				},
			},
			type: 'SUCCESS_FETCH_IMPORT_DATA',
		};

		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );
	} );

	it( 'Should catch error on the fetch importer data action', () => {
		const gen = fetchImporterData();

		// Start fetch action.
		gen.next();

		// Fetch action.
		gen.next();

		// Error action.
		const error = { code: '', message: 'Error' };
		const expectedErrorAction = {
			type: ERROR_FETCH_IMPORT_DATA,
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	it( 'Should return the start fetch import data action', () => {
		const expectedAction = {
			type: START_FETCH_IMPORT_DATA,
		};

		expect( startFetch() ).toEqual( expectedAction );
	} );

	it( 'Should return the success fetch import data action', () => {
		const data = { x: 1 };
		const expectedAction = {
			type: SUCCESS_FETCH_IMPORT_DATA,
			data,
		};

		expect( successFetch( data ) ).toEqual( expectedAction );
	} );

	it( 'Should return the error fetch import data action', () => {
		const error = { code: '', message: 'Error' };
		const expectedAction = {
			type: ERROR_FETCH_IMPORT_DATA,
			error,
		};

		expect( errorFetch( error ) ).toEqual( expectedAction );
	} );

	/**
	 * Start import actions.
	 */
	it( 'Should generate the start import action', () => {
		const gen = submitStartImport();

		// Start action to start the import process.
		const expectedStartImportAction = {
			type: START_START_IMPORT,
		};
		expect( gen.next().value ).toEqual( expectedStartImportAction );

		// Start import action.
		const expectedStartAction = {
			type: FETCH_FROM_API,
			request: {
				method: 'POST',
				path: API_BASE_PATH + 'start',
			},
		};
		expect( gen.next().value ).toEqual( expectedStartAction );

		// Set data action.
		const dataObject = {
			id: 'test',
			status: {
				status: 'pending',
				percentage: 0,
			},
			files: {},
		};

		const expectedSetDataAction = {
			data: {
				id: 'test',
				upload: {},
				import: {
					status: 'pending',
					percentage: 0,
				},
				completedSteps: [ 'upload' ],
			},
			type: 'SUCCESS_START_IMPORT',
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );
	} );

	it( 'Should catch error on the start import action', () => {
		const gen = submitStartImport();

		// Start submit start import action.
		gen.next();

		// Submit start import action.
		gen.next();

		// Error action.
		const error = { code: '', message: 'Error' };
		const expectedErrorAction = {
			type: ERROR_START_IMPORT,
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	it( 'Should return the start start import action', () => {
		const expectedAction = {
			type: START_START_IMPORT,
		};

		expect( startStartImport() ).toEqual( expectedAction );
	} );

	it( 'Should return the success start import action', () => {
		const data = { x: 1 };
		const expectedAction = {
			type: SUCCESS_START_IMPORT,
			data,
		};

		expect( successStartImport( data ) ).toEqual( expectedAction );
	} );

	it( 'Should return the error start import action', () => {
		const error = { code: '', message: 'Error' };
		const expectedAction = {
			type: ERROR_START_IMPORT,
			error,
		};

		expect( errorStartImport( error ) ).toEqual( expectedAction );
	} );

	/**
	 * Upload file actions.
	 */
	it( 'Should generate the upload file action', () => {
		const level = 'test';
		const uploadData = {};

		const gen = uploadFileForLevel( level, uploadData );

		// Start upload action.
		const expectedUploadFileAction = {
			type: START_UPLOAD_IMPORT_DATA_FILE,
			level,
			uploadData,
		};
		expect( gen.next().value ).toEqual( expectedUploadFileAction );

		// File upload request action.
		const expectedApiRequest = {
			type: FETCH_FROM_API,
			request: {
				method: 'POST',
				path: API_BASE_PATH + 'file/' + level,
				body: uploadData,
			},
		};
		expect( gen.next().value ).toEqual( expectedApiRequest );

		// Set data action.
		const dataObject = {
			id: 'test',
			status: {
				status: 'setup',
				percentage: 0,
			},
			files: {},
		};

		const expectedSetDataAction = {
			data: {
				id: 'test',
				upload: {},
				import: {
					status: 'setup',
					percentage: 0,
				},
				completedSteps: [],
			},
			level: 'test',
			type: 'SUCCESS_UPLOAD_IMPORT_DATA_FILE',
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );
	} );

	it( 'Should catch error on the file upload action', () => {
		const formData = {};

		const gen = uploadFileForLevel( 'test', formData );

		// Start file upload action.
		gen.next();

		// File upload request action.
		gen.next();

		const error = { code: '', message: 'Error' };

		// Error action.
		const expectedErrorAction = {
			type: ERROR_UPLOAD_IMPORT_DATA_FILE,
			level: 'test',
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	it( 'Should return the start file upload action', () => {
		const level = 'test';
		const uploadData = {};
		const expectedAction = {
			type: START_UPLOAD_IMPORT_DATA_FILE,
			level,
			uploadData,
		};

		expect( startFileUploadAction( level, uploadData ) ).toEqual(
			expectedAction
		);
	} );

	it( 'Should return the success file upload action', () => {
		const level = 'test';
		const data = {};
		const expectedAction = {
			type: SUCCESS_UPLOAD_IMPORT_DATA_FILE,
			level,
			data,
		};

		expect( successFileUpload( level, data ) ).toEqual( expectedAction );
	} );

	it( 'Should return the error file upload action', () => {
		const level = 'test';
		const error = { code: '', message: 'Test' };
		const expectedAction = {
			type: ERROR_UPLOAD_IMPORT_DATA_FILE,
			level,
			error,
		};

		expect( errorFileUpload( level, error ) ).toEqual( expectedAction );
	} );

	it( 'Should return the error file upload action on an early trigger', () => {
		const level = 'test';
		const error = { code: '', message: 'Test' };
		const expectedAction = {
			type: ERROR_UPLOAD_IMPORT_DATA_FILE,
			level,
			error,
		};

		const gen = throwEarlyUploadError( level, 'Test' );

		expect( gen.next().value ).toEqual( expectedAction );
	} );
} );
