import { render, fireEvent } from '@testing-library/react';

import QueryStringRouter, { Route } from '../query-string-router';
import { updateRouteURL } from '../query-string-router/url-functions';
import Features from './index';

describe( '<Features />', () => {
	afterEach( () => {
		// Clear URL param.
		updateRouteURL( 'step', '' );
	} );

	it( 'Should continue to the ready step when nothing is selected', () => {
		const { container, queryByText } = render(
			<QueryStringRouter paramName="step">
				<Route route="features" defaultRoute>
					<Features />
				</Route>
				<Route route="ready">Ready</Route>
			</QueryStringRouter>
		);

		fireEvent.click( queryByText( 'Continue' ) );

		expect( container.firstChild ).toMatchInlineSnapshot( 'Ready' );
	} );

	it( 'Should continue to the ready step when the user chooses to install later', () => {
		const { container, queryByText } = render(
			<QueryStringRouter paramName="step">
				<Route route="features" defaultRoute>
					<Features />
				</Route>
				<Route route="ready">Ready</Route>
			</QueryStringRouter>
		);

		// Check the first feature.
		fireEvent.click( container.querySelector( 'input[type="checkbox"]' ) );

		// Continue to confirmation.
		fireEvent.click( queryByText( 'Continue' ) );

		// Choose to install later.
		fireEvent.click( queryByText( "I'll do it later" ) );

		expect( container.firstChild ).toMatchInlineSnapshot( 'Ready' );
	} );

	it( 'Should continue to the confirmation and then installation feedback when some feature is selected', () => {
		const { container, queryByText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		// Check the first feature.
		fireEvent.click( container.querySelector( 'input[type="checkbox"]' ) );

		// Continue to confirmation.
		fireEvent.click( queryByText( 'Continue' ) );

		// Confirm the installation.
		fireEvent.click( queryByText( 'Install now' ) );

		expect(
			container.querySelector( '.sensei-onboarding__icon-status' )
		).toBeTruthy();
	} );
} );
