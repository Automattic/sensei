import { API_BASE_PATH, FETCH_FROM_API, SET_STEP_DATA } from './constants';
import { getStepData } from './resolvers';

describe( 'Setup wizard resolvers', () => {
	it( 'Should resolve the getStepData selector generating the correct resolver', () => {
		const gen = getStepData( 'progress', 'test-id', true );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + '?job_id=test-id',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set data action.
		const dataObject = {
			id: 'test-id',
			status: {
				status: 'pending',
				percentage: 44,
			},
			files: {},
		};

		// Set data action.
		const expectedDataObject = {
			jobId: 'test-id',
			progress: {
				status: 'pending',
				percentage: 44,
			},
			upload: {},
			completedSteps: [ 'upload' ],
		};

		const expectedSetDataAction = {
			type: SET_STEP_DATA,
			step: 'progress',
			data: expectedDataObject,
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );

		expect( gen.next().done ).toBeTruthy();
	} );

	it( 'Should not resolve the getStepData selector when flag is not set', () => {
		const gen = getStepData( 'progress', 'test-id' );

		expect( gen.next().done ).toBeTruthy();
	} );
} );
