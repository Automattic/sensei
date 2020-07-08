import { API_BASE_PATH, FETCH_FROM_API, SET_IMPORT_LOG } from './constants';
import { getLogsBySeverity } from './resolvers';

describe( 'Importer resolvers', () => {
	it( 'Should resolve the getLogsBySeverity selector generating the correct resolver', () => {
		const gen = getLogsBySeverity( '123' );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + '123/logs',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set data action.
		const dataObject = { a: 1 };
		const expectedSetDataAction = {
			type: SET_IMPORT_LOG,
			data: dataObject,
		};
		expect( gen.next( dataObject ).value ).toEqual( expectedSetDataAction );

		expect( gen.next().done ).toBeTruthy();
	} );
} );
