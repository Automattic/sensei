/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { registerStructureStore } from '../../shared/structure/structure-store';

jest.mock( '@wordpress/data', () =>
	require( '../../../tests/mocks/wordpress-data' )( {
		dispatch: jest.fn(),
		select: jest.fn(),
	} )
);
jest.mock( '../../shared/structure/structure-store' );

describe( 'Course outline structure store', () => {
	const clientId = 'outline-client-id';
	let storeConfig;
	let replaceInnerBlocks;
	let innerBlocks;

	beforeEach( () => {
		replaceInnerBlocks = jest.fn();
		innerBlocks = [
			{
				name: 'sensei-lms/course-outline-lesson',
				clientId: 'stale-lesson',
				attributes: { id: 1, title: 'Deleted lesson' },
				innerBlocks: [],
			},
		];

		registerStructureStore.mockImplementation( ( config ) => {
			storeConfig = config;
		} );

		select.mockReturnValue( {
			getBlocks: ( id ) =>
				id
					? innerBlocks
					: [
							{
								name: 'sensei-lms/course-outline',
								clientId,
								attributes: {},
								innerBlocks,
							},
					  ],
			getCurrentPostId: () => 1,
		} );
		dispatch.mockReturnValue( { replaceInnerBlocks } );

		// Importing the store registers it, which captures the config under test.
		require( './course-outline-store' );
	} );

	afterEach( () => {
		jest.resetModules();
		jest.clearAllMocks();
	} );

	const runUpdateBlock = ( structure, isAuthoritative ) => {
		const generator = storeConfig.updateBlock( structure, isAuthoritative );

		let step = generator.next();
		while ( ! step.done ) {
			step = generator.next( step.value );
		}
	};

	it( 'Keeps blocks when an empty structure comes from a load', () => {
		runUpdateBlock( [], false );

		expect( replaceInnerBlocks ).not.toHaveBeenCalled();
	} );

	it( 'Drops blocks when an empty structure comes from a save', () => {
		runUpdateBlock( [], true );

		expect( replaceInnerBlocks ).toHaveBeenCalledWith(
			clientId,
			[],
			false
		);
	} );
} );
