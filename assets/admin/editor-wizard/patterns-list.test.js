/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { parse } from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import PatternsList from './patterns-list';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	BlockPreview: () => null,
} ) );
jest.mock( '@wordpress/blocks', () => ( {
	...jest.requireActual( '@wordpress/blocks' ),
	parse: jest.fn(),
} ) );

describe( '<PatternsList />', () => {
	const mockPatternSelectors = ( registeredPatterns = [] ) => {
		const getBlockPatterns = jest.fn( () => registeredPatterns );

		useSelect.mockImplementation( ( callback ) =>
			callback( ( store ) => {
				if ( coreStore === store ) {
					return { getBlockPatterns };
				}

				if ( editorStore === store ) {
					return { getEditorSettings: () => ( {} ) };
				}
			} )
		);

		return getBlockPatterns;
	};

	it( 'Should show warning when no layouts available.', () => {
		mockPatternSelectors();

		const { queryByText } = render(
			<PatternsList onChoose={ () => {} } />
		);

		expect(
			queryByText( 'No layouts available for this theme.' )
		).toBeVisible();
	} );

	it( 'Should load and select layouts from registered pattern data', () => {
		const blocks = [ { name: 'core/paragraph', attributes: {} } ];
		parse.mockReturnValue( blocks );
		const getBlockPatterns = mockPatternSelectors( [
			{
				name: 'sensei-lms/course-default',
				title: 'Default Course',
				categories: [ 'sensei-lms' ],
				blockTypes: [ 'sensei-lms/post-content' ],
				content: '<!-- wp:paragraph /-->',
			},
		] );
		const onChoose = jest.fn();

		const { getByText } = render( <PatternsList onChoose={ onChoose } /> );

		fireEvent.click( getByText( 'Default Course' ) );

		expect( getBlockPatterns ).toHaveBeenCalled();
		expect( onChoose ).toHaveBeenCalledWith(
			blocks,
			'sensei-lms/course-default',
			undefined
		);
	} );
} );
