import { getSelectedKeys } from './data';

describe( 'getSelectedKeys', () => {
	it( 'returns keys for object entries where the value is true', () => {
		const keys = getSelectedKeys( { a: true, b: false, c: true } );

		expect( keys ).toEqual( [ 'a', 'c' ] );
	} );
} );
