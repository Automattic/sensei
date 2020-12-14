import { FETCH_FROM_API } from './constants';
import controls from './controls';

jest.mock( '@wordpress/api-fetch', () => () => 'result' );

describe( 'Importer controls', () => {
	it( 'Should fetch from API', () => {
		const action = {
			request: {
				path: '/',
			},
		};

		expect( controls[ FETCH_FROM_API ]( action ) ).toEqual( 'result' );
	} );
} );
