import { useEffect } from '@wordpress/element';
import { render } from '@testing-library/react';

import QueryStringRouter, { Route, useQueryStringRouter } from './index';

const GoToSecondRoute = () => {
	const { goTo } = useQueryStringRouter();

	useEffect( () => {
		goTo( 'two' );
	}, [ goTo ] );

	return null;
};

describe( '<Route />', () => {
	it( 'Should render the default route', () => {
		const { queryByText } = render(
			<QueryStringRouter queryStringName="route">
				<Route route="one" defaultRoute>
					One
				</Route>
				<Route route="two">Two</Route>
			</QueryStringRouter>
		);

		expect( queryByText( 'One' ) ).toBeTruthy();
	} );

	it( 'Should render the current route', () => {
		const { queryByText } = render(
			<QueryStringRouter queryStringName="route">
				<Route route="one" defaultRoute>
					One
				</Route>
				<Route route="two">Two</Route>
				<GoToSecondRoute />
			</QueryStringRouter>
		);

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );
} );
