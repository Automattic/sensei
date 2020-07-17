import { render } from '@testing-library/react';
import { ImportProgressPage } from './import-progress-page';

jest.mock( '@wordpress/api-fetch', () => () => Promise.resolve() );
jest.mock( './use-progress-polling', () => jest.fn() );

describe( '<ImportProgressPage /> progress bar', () => {
	it( 'should show percent complete', async () => {
		const state = {
			status: 'pending',
			percentage: 33,
		};

		const { getByRole } = render(
			<ImportProgressPage jobId="test-job" state={ state } />
		);

		expect( getByRole( 'progressbar' ).getAttribute( 'value' ) ).toEqual(
			'33'
		);
	} );
} );
