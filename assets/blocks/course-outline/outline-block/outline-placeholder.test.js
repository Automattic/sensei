/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import OutlinePlaceholder from './outline-placeholder';
import userEvent from '@testing-library/user-event';

window.ResizeObserver =
	window.ResizeObserver ||
	jest.fn().mockImplementation( () => ( {
		disconnect: jest.fn(),
		observe: jest.fn(),
		unobserve: jest.fn(),
	} ) );

describe( '<OutlinePlaceholder />', () => {
	const addBlocksMock = jest.fn();

	beforeAll( () => {
		window.sensei = { featureFlags: {} };
		window.sensei.featureFlags.course_outline_ai = true;
	} );

	it( 'Should render the outline placeholder correctly when feature flag is enabled', () => {
		const { getByText } = render(
			<OutlinePlaceholder addBlocks={ addBlocksMock } />
		);

		expect( getByText( 'Start with blank' ) ).toBeVisible();

		expect( getByText( 'Generate with AI' ) ).toBeVisible();
	} );

	it( 'Should create empty lessons', async () => {
		const { getByRole } = render(
			<OutlinePlaceholder addBlocks={ addBlocksMock } />
		);

		await userEvent.click(
			getByRole( 'button', { name: 'Start with blank' } )
		);

		expect( addBlocksMock ).toHaveBeenCalled();
	} );

	it( 'Should open the tailored modal', async () => {
		const openTailoredModalMock = jest.fn();

		const { getByRole } = render(
			<OutlinePlaceholder
				addBlocks={ addBlocksMock }
				openTailoredModal={ openTailoredModalMock }
			/>
		);

		await userEvent.click(
			getByRole( 'button', { name: 'Generate with AI Pro' } )
		);

		expect( openTailoredModalMock ).toHaveBeenCalled();
	} );

	describe( 'when the course_outline_ai is off', () => {
		it( 'Should render the outline placeholder correctly when feature flag is disabled', () => {
			const addBlockMock = jest.fn();

			window.sensei.featureFlags.course_outline_ai = false;

			const { getByText, getAllByText } = render(
				<OutlinePlaceholder addBlock={ addBlockMock } />
			);

			expect(
				// This has an extra accessibility text.
				getAllByText(
					'You can use modules to group related lessons together.',
					{ exact: false }
				)[ 0 ]
			).toBeTruthy();
			expect( getByText( 'Create a module' ) ).toBeTruthy();
			expect( getByText( 'Create a lesson' ) ).toBeTruthy();
		} );
	} );
} );
