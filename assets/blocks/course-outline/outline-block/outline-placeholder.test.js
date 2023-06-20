/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import OutlinePlaceholder from './outline-placeholder';

describe( '<OutlinePlaceholder />', () => {
	const addBlockMock = jest.fn();

	beforeAll( () => {
		window.sensei = { ...window?.sensei, aiCourseOutline: true };
	} );

	it( 'Should render the outline generated options', () => {
		const { getByRole } = render(
			<OutlinePlaceholder addBlock={ addBlockMock } />
		);

		expect(
			getByRole( 'button', { name: 'Generate with AI Pro' } )
		).toBeVisible();

		expect(
			getByRole( 'button', { name: 'Start with blank' } )
		).toBeVisible();
	} );

	describe( 'when the feature aiCourseOutline off', () => {
		it( 'Should render the outline placeholder correctly when feature flag is disabled', () => {
			window.sensei = { ...window.sensei, aiCourseOutline: false };
			const { getByText } = render(
				<OutlinePlaceholder addBlock={ addBlockMock } />
			);

			expect(
				getByText(
					'You can use modules to group related lessons together.',
					{ exact: false }
				)
			).toBeTruthy();
			expect( getByText( 'Create a module' ) ).toBeTruthy();
			expect( getByText( 'Create a lesson' ) ).toBeTruthy();
		} );
	} );
} );
