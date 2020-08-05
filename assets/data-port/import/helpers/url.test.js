import { API_BASE_PATH } from '../data/constants';
import { buildJobEndpointUrl } from './url';

describe( 'URL helpers', () => {
	it( 'buildJobEndpointUrl with path', () => {
		const expectedUrl = API_BASE_PATH + 'test-id/very/long/path/eh';

		expect(
			buildJobEndpointUrl( 'test-id', [ 'very', 'long', 'path', 'eh' ] )
		).toEqual( expectedUrl );
	} );

	it( 'buildJobEndpointUrl without path', () => {
		const expectedUrl = API_BASE_PATH + 'test-id';

		expect( buildJobEndpointUrl( 'test-id' ) ).toEqual( expectedUrl );
	} );

	it( 'buildJobEndpointUrl without jobId', () => {
		const expectedUrl = API_BASE_PATH + 'very/long/path/eh';

		expect(
			buildJobEndpointUrl( null, [ 'very', 'long', 'path', 'eh' ] )
		).toEqual( expectedUrl );
	} );
} );
