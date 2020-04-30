import { render, fireEvent } from '@testing-library/react';

import QueryStringRouter, { Route, useQueryStringRouter } from './index';
import { mockSearch } from '../../tests-helper/functions';

const NextButton = ( { nextKey } ) => {
	const { goTo } = useQueryStringRouter();

	return (
		<button
			onClick={ () => {
				goTo( nextKey );
			} }
			data-testid="next-button"
		/>
	);
};

describe( '<QueryStringRouter />', () => {
	it( 'Should navigate to the next route', () => {
		const { queryByText, queryByTestId } = render(
			<QueryStringRouter paramName="route">
				<Route route="one" defaultRoute>
					One <NextButton nextKey="two" />
				</Route>
				<Route route="two">
					Two <NextButton nextKey="three" />
				</Route>
			</QueryStringRouter>
		);

		fireEvent.click( queryByTestId( 'next-button' ) );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );

	it( 'Should go to the correct route after on popstate', () => {
		const { queryByText } = render(
			<QueryStringRouter paramName="route">
				<Route route="one" defaultRoute>
					One <NextButton nextKey="two" />
				</Route>
				<Route route="two">
					Two <NextButton nextKey="three" />
				</Route>
			</QueryStringRouter>
		);

		mockSearch( 'route=two' );
		fireEvent.popState( window );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );
} );
