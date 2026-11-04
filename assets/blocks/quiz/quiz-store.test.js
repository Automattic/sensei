/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { select, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { QUIZ_STORE, useQuizStructure } from './quiz-store';

jest.mock( '@wordpress/data', () =>
	require( '../../../tests/mocks/wordpress-data' )( {
		useSelect: jest.fn(),
	} )
);
jest.mock( '@wordpress/data-controls' );

const setPreviewMode = ( isPreviewMode ) => {
	useSelect.mockImplementation( ( mapSelect ) =>
		mapSelect( ( store ) =>
			'core/block-editor' === store
				? { getSettings: () => ( { isPreviewMode } ) }
				: {}
		)
	);
};

describe( 'useQuizStructure', () => {
	it( 'Should keep the real quiz block linked when a block preview mounts and unmounts', () => {
		setPreviewMode( false );
		renderHook( () => useQuizStructure( { clientId: 'real-block' } ) );
		setPreviewMode( true );
		const preview = renderHook( () =>
			useQuizStructure( { clientId: 'preview-block' } )
		);

		preview.unmount();

		expect( select( QUIZ_STORE ).getBlock() ).toBe( 'real-block' );
	} );
} );
