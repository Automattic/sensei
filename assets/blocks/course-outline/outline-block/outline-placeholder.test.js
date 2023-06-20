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
		window.sensei = { feature_flags: {} };
	} );

	it( 'Should render the outline placeholder correctly when feature flag is enabled', () => {
		window.sensei.feature_flags.course_outline_ai = true;

		const { container, getByText } = render(
			<OutlinePlaceholder addBlock={ addBlockMock } />
		);

		expect(
			getByText( 'Build and display a course outline.' )
		).toBeTruthy();
		expect( container.querySelector( '.is-blank' ) ).toBeTruthy();
		expect( container.querySelector( '.is-ai' ) ).toBeTruthy();
	} );

	it( 'Should render the outline placeholder correctly when feature flag is disabled', () => {
		window.sensei.feature_flags.course_outline_ai = false;

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
