/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import QueryStringRouter, { Route, useQueryStringRouter } from './index';
import { mockSearch } from '../../tests-helper/functions';

const routes = [ 'one', 'two', 'three' ];

const NavigationButtons = ( { goToKey } ) => {
	const { goTo, goNext } = useQueryStringRouter();

	return (
		<>
			<button
				onClick={ () => {
					goTo( goToKey );
				} }
				data-testid="go-to-button"
			/>
			<button
				onClick={ () => {
					goNext();
				} }
				data-testid="go-next-button"
			/>
		</>
	);
};

describe( '<QueryStringRouter />', () => {
	beforeEach( () => {
		mockSearch( '' );
	} );

	it( 'Should navigate to the route', () => {
		const { queryByText, queryByTestId } = render(
			<QueryStringRouter
				paramName="route"
				routes={ routes }
				defaultRoute="one"
			>
				<Route route="one">
					One <NavigationButtons goToKey="three" />
				</Route>
				<Route route="two">Two</Route>
				<Route route="three">Three</Route>
			</QueryStringRouter>
		);

		fireEvent.click( queryByTestId( 'go-to-button' ) );

		expect( queryByText( 'Three' ) ).toBeTruthy();
	} );

	it( 'Should navigate to the next route', () => {
		const { queryByText, queryByTestId } = render(
			<QueryStringRouter
				paramName="route"
				routes={ routes }
				defaultRoute="one"
			>
				<Route route="one">
					One <NavigationButtons />
				</Route>
				<Route route="two">Two</Route>
				<Route route="three">Three</Route>
			</QueryStringRouter>
		);

		fireEvent.click( queryByTestId( 'go-next-button' ) );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );

	it( 'Should go to the correct route after on popstate', () => {
		const { queryByText } = render(
			<QueryStringRouter
				paramName="route"
				routes={ routes }
				defaultRoute="one"
			>
				<Route route="one">
					One <NavigationButtons goToKey="two" />
				</Route>
				<Route route="two">
					Two <NavigationButtons goToKey="three" />
				</Route>
			</QueryStringRouter>
		);

		mockSearch( 'route=two' );
		fireEvent.popState( window );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );
} );
