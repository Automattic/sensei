import {
	getFetchError,
	getJobId,
	getNavigationSteps,
	getStepData,
	isCompleteStep,
	isFetching,
	isReadyToStart,
} from './selectors';

describe( 'Importer selectors', () => {
	it( 'Should get is fetching data', () => {
		const state = {
			isFetching: true,
		};

		expect( isFetching( state ) ).toBeTruthy();
	} );

	it( 'Should get job id', () => {
		const state = {
			jobId: 'test',
		};

		expect( getJobId( state ) ).toEqual( state.jobId );
	} );

	it( 'Should get the fetch error', () => {
		const error = { code: '', message: 'Error message' };
		const state = {
			fetchError: error,
		};

		expect( getFetchError( state ) ).toEqual( error );
	} );

	it( 'Should get step data', () => {
		const stepData = {
			test: 'dinosaur',
		};

		const state = {
			stepA: stepData,
		};

		expect( getStepData( state, 'stepA' ) ).toEqual( stepData );
	} );

	it( 'Should get navigation steps', () => {
		const state = {
			completedSteps: [ 'upload' ],
		};

		const navSteps = getNavigationSteps( state );

		expect( navSteps[ 0 ].isComplete ).toBeTruthy();
		expect( navSteps[ 0 ].isNext ).toBeFalsy();
		expect( navSteps[ 1 ].isComplete ).toBeFalsy();
		expect( navSteps[ 1 ].isNext ).toBeTruthy();
		expect( navSteps[ 2 ].isComplete ).toBeFalsy();
		expect( navSteps[ 2 ].isNext ).toBeFalsy();
	} );

	it( 'Should return whether step is complete or not', () => {
		const state = {
			completedSteps: [ 'welcome' ],
		};

		expect( isCompleteStep( state, 'welcome' ) ).toBeTruthy();
		expect( isCompleteStep( state, 'other' ) ).toBeFalsy();
	} );

	it( 'Should return not ready to start when no files have been uploaded', () => {
		const state = {
			upload: {
				courses: {
					isUploaded: false,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: null,
				},
				lessons: {
					isUploaded: false,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: null,
				},
				questions: {
					isUploaded: false,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: null,
				},
			},
		};

		expect( isReadyToStart( state ) ).toBeFalsy();
	} );

	it( 'Should return as ready to start when files have been uploaded', () => {
		const state = {
			upload: {
				courses: {
					isUploaded: false,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: null,
				},
				lessons: {
					isUploaded: true,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: 'test.csv',
				},
				questions: {
					isUploaded: false,
					isUploading: false,
					hasError: false,
					errorMsg: null,
					filename: null,
				},
			},
		};

		expect( isReadyToStart( state ) ).toBeTruthy();
	} );
} );
