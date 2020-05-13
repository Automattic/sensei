import { render } from '@testing-library/react';

import FeatureStatus, {
	LOADING_STATUS,
	ERROR_STATUS,
	SUCCESS_STATUS,
} from './feature-status';

describe( '<FeatureStatus />', () => {
	describe.each`
		status            | expected
		${LOADING_STATUS} | ${'Installing plugin'}
		${ERROR_STATUS}   | ${'Error installing plugin'}
		${SUCCESS_STATUS} | ${'Plugin installed'}
	`( 'Should render with status $status', ( { status, expected } ) => {
		it( `Render correctly status ${ status }`, () => {
			const { queryByText } = render(
				<FeatureStatus status={ status } />
			);

			expect( queryByText( expected ) ).toBeTruthy();
		} );
	} );
} );
