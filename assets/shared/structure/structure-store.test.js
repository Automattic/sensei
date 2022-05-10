/**
 * WordPress dependencies
 */
import { dispatch, registerStore } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import mockEditorStore from '../../tests-helper/mock-editor-post-save-store';
import { registerStructureStore } from './structure-store';

jest.mock( '@wordpress/data-controls' );
describe( 'Structure store', () => {
	const STORE = 'test';
	let store, unsubscribe;
	beforeAll( () => {} );
	beforeEach( () => {
		store = {
			storeName: STORE,
			getEndpoint: jest.fn(),
			updateBlock: jest.fn(),
			readBlock: jest.fn(),
		};

		( { unsubscribe } = registerStructureStore( store ) );
		registerStore( 'core/editor', mockEditorStore );

		apiFetch.mockClear();
		store.getEndpoint.mockImplementation( function* () {
			return 'test-api/1';
		} );
	} );
	afterEach( () => {
		unsubscribe();
	} );

	it( 'Updates block with result from from REST API', () => {
		apiFetch.mockReturnValueOnce( 'server' );

		dispatch( STORE ).loadStructure();

		expect( apiFetch ).toHaveBeenCalledWith( {
			method: 'GET',
			path: '/sensei-internal/v1/test-api/1',
		} );
		expect( store.updateBlock ).toHaveBeenCalledWith( 'server' );
	} );

	it( 'Reads structure from block', () => {
		store.readBlock.mockReturnValue( 'old' );

		dispatch( STORE ).startPostSave();

		expect( store.readBlock ).toHaveBeenCalled();
	} );

	it( 'Saves structure when post is being saved', () => {
		const savePost = jest.spyOn( dispatch( 'core/editor' ), 'savePost' );
		store.readBlock.mockReturnValue( 'new' );
		apiFetch.mockReturnValue( 'new' );
		dispatch( 'core/editor' ).savePost();
		expect( apiFetch ).toHaveBeenCalledWith( {
			method: 'POST',
			path: '/sensei-internal/v1/test-api/1',
			data: 'new',
		} );

		// Check if there is not an infinite loop.
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( savePost ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Re-saves post on change after structure save', () => {
		const savePost = jest.spyOn( dispatch( 'core/editor' ), 'savePost' );
		store.readBlock
			.mockReturnValueOnce( 'old' )
			.mockReturnValueOnce( 'new' );

		apiFetch.mockReturnValue( 'new' );

		dispatch( 'core/editor' ).savePost();

		expect( savePost ).toHaveBeenCalledTimes( 2 );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );
} );
