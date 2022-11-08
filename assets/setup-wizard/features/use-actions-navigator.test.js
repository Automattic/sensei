/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useActionsNavigator from './use-actions-navigator';

const actionsSample = [
	{
		label: 'a',
		action: () => Promise.resolve(),
	},
	{
		label: 'b',
		action: () => Promise.resolve(),
	},
];

describe( 'useActionsNavigator', () => {
	const TestComponent = ( { actions } ) => {
		const { label, percentage } = useActionsNavigator( actions );

		return (
			<div>
				<div>{ label }</div>
				<div>{ percentage }</div>
			</div>
		);
	};

	it( 'should render the first step properly', () => {
		const { findByText } = render(
			<TestComponent actions={ actionsSample } />
		);

		expect( findByText( 'a' ) ).toBeTruthy();
		expect( findByText( '50' ) ).toBeTruthy();
	} );
} );
