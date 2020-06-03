import { API_BASE_PATH, FETCH_FROM_API, SET_STEP_DATA } from './constants';
import { getStepData } from './resolvers';

describe( 'Setup wizard resolvers', () => {
	it( 'Should resolve the getStepData selector generating the correct resolver', () => {
		const gen = getStepData( 'features', true );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'features',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set data action.
		const dataObject = { selected: [], options: [] };
		const expectedSetDataAction = {
			type: SET_STEP_DATA,
			step: 'features',
			data: dataObject,
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );

		expect( gen.next().done ).toBeTruthy();
	} );

	it( 'Should not resolve the getStepData selector when flag is not set', () => {
		const gen = getStepData( 'features' );

		expect( gen.next().done ).toBeTruthy();
	} );
} );
