import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_CURRENT_JOB_STATE,
	SUCCESS_FETCH_CURRENT_JOB_STATE,
	ERROR_FETCH_CURRENT_JOB_STATE,
	START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	START_DELETE_IMPORT_DATA_FILE,
	ERROR_DELETE_IMPORT_DATA_FILE,
	SUCCESS_DELETE_IMPORT_DATA_FILE,
} from './constants';

import {
	fetchFromAPI,
	fetchCurrentJobState,
	submitStartImport,
	startImport,
	successStartImport,
	errorStartImport,
	uploadFileForLevel,
	errorFileUpload,
	startFileUploadAction,
	successFileUpload,
	throwEarlyUploadError,
	updateJobState,
	deleteLevelFile,
	startDeleteLevelFileAction,
	successDeleteLevelFileAction,
	errorDeleteLevelFileAction,
} from './actions';

const RESPONSE_FULL = {
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
	results: {
		question: { success: 0, error: 0 },
		course: { success: 0, error: 0 },
		lesson: { success: 0, error: 0 },
	},
};

const RESPONSE_SETUP = {
	id: 'test',
	status: {
		status: 'setup',
		percentage: 0,
	},
	files: {},
	results: {},
};
const RESPONSE_PENDING = {
	id: 'test',
	status: {
		status: 'pending',
		percentage: 0,
	},
	files: {},
	results: {},
};

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
	it( 'Should generate the get current job state importer data action', () => {
		const gen = fetchCurrentJobState();

		// Start fetch action.
		const expectedStartFetchAction = {
			type: START_FETCH_CURRENT_JOB_STATE,
		};

		expect( gen.next().value ).toEqual( expectedStartFetchAction );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'active',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		const expectedSetDataAction = {
			data: {
				completedSteps: [],
				jobId: 'test',
				progress: {
					percentage: 0,
					status: 'setup',
				},
				upload: {
					courses: {
						filename: 'courses-sample.csv',
						isUploaded: true,
					},
				},
				done: {
					results: {
						question: { success: 0, error: 0 },
						course: { success: 0, error: 0 },
						lesson: { success: 0, error: 0 },
					},
				},
			},
			type: SUCCESS_FETCH_CURRENT_JOB_STATE,
		};

		expect( gen.next( RESPONSE_FULL ).value ).toEqual(
			expectedSetDataAction
		);
	} );

	it( 'Should catch error on the get current job state action', () => {
		const gen = fetchCurrentJobState();

		// Start fetch action.
		gen.next();

		// Fetch action.
		gen.next();

		// Error action.
		const error = { code: '', message: 'Error' };
		const expectedErrorAction = {
			type: ERROR_FETCH_CURRENT_JOB_STATE,
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	/**
	 * Fetch importer data action.
	 */
	it( 'Should generate the update job state action', () => {
		const gen = updateJobState( 'test-id' );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'test-id',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		const expectedSetDataAction = {
			data: {
				completedSteps: [],
				jobId: 'test',
				progress: {
					percentage: 0,
					status: 'setup',
				},
				upload: {
					courses: {
						filename: 'courses-sample.csv',
						isUploaded: true,
					},
				},
				done: {
					results: {
						question: { success: 0, error: 0 },
						course: { success: 0, error: 0 },
						lesson: { success: 0, error: 0 },
					},
				},
			},
			type: 'SET_JOB_STATE',
		};

		expect( gen.next( RESPONSE_FULL ).value ).toEqual(
			expectedSetDataAction
		);
	} );

	/**
	 * Start import actions.
	 */
	it( 'Should generate the start import action', () => {
		const gen = submitStartImport( 'test-id' );

		// Start action to start the import process.
		const expectedStartImportAction = {
			type: START_IMPORT,
		};
		expect( gen.next().value ).toEqual( expectedStartImportAction );

		// Start import action.
		const expectedStartAction = {
			type: FETCH_FROM_API,
			request: {
				method: 'POST',
				path: API_BASE_PATH + 'test-id/start',
			},
		};
		expect( gen.next().value ).toEqual( expectedStartAction );

		const expectedSetDataAction = {
			data: {
				jobId: 'test',
				upload: {},
				progress: {
					status: 'pending',
					percentage: 0,
				},
				completedSteps: [ 'upload' ],
				done: {
					results: {},
				},
			},
			type: 'SUCCESS_START_IMPORT',
		};
		expect( gen.next( RESPONSE_PENDING ).value ).toEqual(
			expectedSetDataAction
		);
	} );

	it( 'Should catch error on the start import action', () => {
		const gen = submitStartImport( 'test-id' );

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
			type: START_IMPORT,
		};

		expect( startImport() ).toEqual( expectedAction );
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

		const gen = uploadFileForLevel( 'test-id', level, uploadData );

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
				path: API_BASE_PATH + 'test-id/file/' + level,
				body: uploadData,
			},
		};
		expect( gen.next().value ).toEqual( expectedApiRequest );

		const expectedSetDataAction = {
			data: {
				jobId: 'test',
				upload: {},
				progress: {
					status: 'setup',
					percentage: 0,
				},
				completedSteps: [],
				done: {
					results: {},
				},
			},
			level: 'test',
			type: 'SUCCESS_UPLOAD_IMPORT_DATA_FILE',
		};
		expect( gen.next( RESPONSE_SETUP ).value ).toEqual(
			expectedSetDataAction
		);
	} );

	it( 'Should catch error on the file upload action', () => {
		const formData = {};

		const gen = uploadFileForLevel( 'test-id', 'test', formData );

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

		expect( throwEarlyUploadError( level, 'Test' ) ).toEqual(
			expectedAction
		);
	} );

	/**
	 * Delete level file actions.
	 */
	it( 'Should generate the delete level file action', () => {
		const level = 'test';

		const gen = deleteLevelFile( 'test-id', level );

		// Start delete level file action.
		const expectedDeleteFileAction = {
			type: START_DELETE_IMPORT_DATA_FILE,
			level,
		};
		expect( gen.next().value ).toEqual( expectedDeleteFileAction );

		// File upload request action.
		const expectedApiRequest = {
			type: FETCH_FROM_API,
			request: {
				method: 'DELETE',
				path: API_BASE_PATH + 'test-id/file/' + level,
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
			results: {},
		};

		const expectedSetDataAction = {
			data: {
				jobId: 'test',
				upload: {},
				progress: {
					status: 'setup',
					percentage: 0,
				},
				done: {
					results: {},
				},
				completedSteps: [],
			},
			level: 'test',
			type: 'SUCCESS_DELETE_IMPORT_DATA_FILE',
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );
	} );

	it( 'Should catch error on the level file delete action', () => {
		const gen = deleteLevelFile( 'test-id', 'test' );

		// Start file upload action.
		gen.next();

		// File upload request action.
		gen.next();

		const error = { code: '', message: 'Error' };

		// Error action.
		const expectedErrorAction = {
			type: ERROR_DELETE_IMPORT_DATA_FILE,
			level: 'test',
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	it( 'Should return the start file delete action', () => {
		const level = 'test';
		const expectedAction = {
			type: START_DELETE_IMPORT_DATA_FILE,
			level,
		};

		expect( startDeleteLevelFileAction( level ) ).toEqual( expectedAction );
	} );

	it( 'Should return the success file delete action', () => {
		const level = 'test';
		const data = {};
		const expectedAction = {
			type: SUCCESS_DELETE_IMPORT_DATA_FILE,
			level,
			data,
		};

		expect( successDeleteLevelFileAction( level, data ) ).toEqual(
			expectedAction
		);
	} );

	it( 'Should return the error file delete action', () => {
		const level = 'test';
		const error = { code: '', message: 'Test' };
		const expectedAction = {
			type: ERROR_DELETE_IMPORT_DATA_FILE,
			level,
			error,
		};

		expect( errorDeleteLevelFileAction( level, error ) ).toEqual(
			expectedAction
		);
	} );
} );
