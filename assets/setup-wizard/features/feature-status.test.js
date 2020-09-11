import { render } from '@testing-library/react';

import FeatureStatus, {
	INSTALLING_STATUS,
	ERROR_STATUS,
	INSTALLED_STATUS,
} from './feature-status';

describe( '<FeatureStatus />', () => {
	describe.each`
		status                 | expected
		${ INSTALLING_STATUS } | ${ 'Installing plugin' }
		${ ERROR_STATUS }      | ${ 'Error installing plugin' }
		${ INSTALLED_STATUS }  | ${ 'Plugin installed' }
	`( 'Should render with status $status', ( { status, expected } ) => {
		it( `Render correctly status ${ status }`, () => {
			const { queryByText } = render(
				<FeatureStatus status={ status } />
			);

			expect( queryByText( expected ) ).toBeTruthy();
		} );
	} );
} );
