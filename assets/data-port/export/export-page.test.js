/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ExportPage } from './export-page';

describe( '<ExportPage />', () => {
	it( 'shows the mode picker when no job is in progress', () => {
		const { queryByText } = render( <ExportPage job={ null } /> );

		expect(
			queryByText( 'How would you like to export your content?' )
		).toBeTruthy();
	} );

	it( 'shows progress screen if there is a job', () => {
		const { getByRole } = render(
			<ExportPage job={ { status: 'pending', percent: 0 } } />
		);

		expect( getByRole( 'progressbar' ) ).toBeTruthy();
	} );
} );
