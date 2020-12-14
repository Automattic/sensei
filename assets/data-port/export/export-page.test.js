import { render } from '@testing-library/react';
import { ExportPage } from './export-page';

describe( '<ExportPage />', () => {
	it( 'shows content selection screen if no job is in progress', () => {
		const { queryByText } = render( <ExportPage job={ null } /> );

		expect(
			queryByText( 'Which type of content would you like to export?' )
		).toBeTruthy();
	} );

	it( 'shows progress screen if there is a job', () => {
		const { getByRole } = render(
			<ExportPage job={ { status: 'pending', percent: 0 } } />
		);

		expect( getByRole( 'progressbar' ) ).toBeTruthy();
	} );
} );
