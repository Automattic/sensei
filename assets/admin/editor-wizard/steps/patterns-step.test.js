/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';

jest.mock( '@wordpress/data' );

describe( '<PatternsStep />', () => {
	it( 'Should show warning when no layouts available.', () => {
		useDispatch.mockReturnValue( {
			resetEditorBlocks: jest.fn(),
			editPost: jest.fn(),
		} );
		useSelect.mockReturnValue( {
			availableTemplates: [],
			patterns: [],
		} );

		const { queryAllByText } = render(
			<PatternsStep title="A" replaces={ {} } onCompletion={ () => {} } />
		);

		expect( queryAllByText( 'Layouts are not available' ) ).toHaveLength(
			2
		);
		expect(
			queryAllByText( 'Layouts are not available' )[ 1 ]
		).toBeVisible();
	} );
} );
