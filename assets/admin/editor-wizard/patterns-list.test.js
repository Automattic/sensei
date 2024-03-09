/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternsList from './patterns-list';

jest.mock( '@wordpress/data' );

describe( '<PatternsList />', () => {
	it( 'Should show warning when no layouts available.', () => {
		useSelect.mockReturnValue( {
			patterns: [],
		} );

		const { queryByText } = render(
			<PatternsList onChoose={ () => {} } />
		);

		expect(
			queryByText( 'No layouts available for this theme.' )
		).toBeVisible();
	} );
} );
