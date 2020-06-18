import reducer from './reducer';
import {
	START_FETCH_IMPORT_DATA,
	SUCCESS_FETCH_IMPORT_DATA,
	ERROR_FETCH_IMPORT_DATA,
	START_START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
} from './constants';

describe( 'Importer reducer', () => {
	it( 'Should set isFetching to true on START_FETCH_IMPORT_DATA action', () => {
		const state = reducer( undefined, {
			type: START_FETCH_IMPORT_DATA,
		} );

		expect( state.isFetching ).toBeTruthy();
	} );

	it( 'Should set isFetching to false and update data on SUCCESS_FETCH_IMPORT_DATA action', () => {
		const data = {
			test: 'data',
		};

		const state = reducer( undefined, {
			type: SUCCESS_FETCH_IMPORT_DATA,
			data,
		} );

		expect( state.isFetching ).toBeFalsy();
		expect( state.test ).toBe( data.test );
	} );

	it( 'Should set isFetching to false and set fetchError on ERROR_FETCH_IMPORT_DATA action', () => {
		const error = {
			code: '',
			message: 'test',
		};

		const state = reducer( undefined, {
			type: ERROR_FETCH_IMPORT_DATA,
			error,
		} );

		expect( state.isFetching ).toBeFalsy();
		expect( state.fetchError ).toBe( error );
	} );

	it( 'Should set isSubmitting to true in upload step on START_START_IMPORT action', () => {
		const state = reducer( undefined, {
			type: START_START_IMPORT,
		} );

		expect( state.upload.isSubmitting ).toBeTruthy();
	} );

	it( 'Should set isSubmitting to false and errorMsg to the error message on ERROR_START_IMPORT action', () => {
		const error = {
			code: '',
			message: 'test',
		};

		const state = reducer( undefined, {
			type: ERROR_START_IMPORT,
			error,
		} );

		expect( state.upload.isSubmitting ).toBeFalsy();
		expect( state.upload.errorMsg ).toBe( error.message );
	} );

	it( 'Should set isSubmitting to false and update upload and import state data on SUCCESS_START_IMPORT action', () => {
		const data = {
			completedSteps: [],
			progress: {
				dinosaur: 'test',
			},
		};

		const state = reducer( undefined, {
			type: SUCCESS_START_IMPORT,
			data,
		} );

		expect( state.upload.isSubmitting ).toBeFalsy();
		expect( state.progress.dinosaur ).toBe( data.progress.dinosaur );
	} );

	it( 'Should set inProgress to true for file level state on START_UPLOAD_IMPORT_DATA_FILE action', () => {
		const level = 'questions';
		const state = reducer( undefined, {
			type: START_UPLOAD_IMPORT_DATA_FILE,
			level,
		} );

		expect( state.upload.levels[ level ].inProgress ).toBeTruthy();
	} );

	it( 'Should set inProgress to false and update level state on SUCCESS_UPLOAD_IMPORT_DATA_FILE action', () => {
		const level = 'questions';
		const data = {
			upload: {
				levels: {
					questions: {
						filename: 'test.csv',
						isUploaded: true,
					},
				},
			},
		};
		const state = reducer( undefined, {
			type: SUCCESS_UPLOAD_IMPORT_DATA_FILE,
			level,
			data,
		} );

		expect( state.upload.levels[ level ].inProgress ).toBeFalsy();
		expect( state.upload.levels[ level ].isUploaded ).toBeTruthy();
	} );

	it( 'Should set inProgress to false, hasError to true, and errorMsg for file level on ERROR_UPLOAD_IMPORT_DATA_FILE action', () => {
		const level = 'questions';
		const error = {
			code: '',
			message: 'test',
		};

		const state = reducer( undefined, {
			type: ERROR_UPLOAD_IMPORT_DATA_FILE,
			error,
			level,
		} );

		expect( state.upload.levels[ level ].inProgress ).toBeFalsy();
		expect( state.upload.levels[ level ].errorMsg ).toBe( error.message );
	} );
} );
