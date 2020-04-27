import { render, fireEvent } from '@testing-library/react';

import { QueryStringRouter, useQueryStringRouter } from './index';
import ContentContainer from '../content-container';
import { mockSearch } from '../../tests-helper/functions';

const ContainerWithNextButton = ( { nextKey, children } ) => {
	const { updateRoute } = useQueryStringRouter();

	return (
		<div>
			{ children }
			<button
				onClick={ () => {
					updateRoute( nextKey );
				} }
				data-testid="next-button"
			>
				{ 'Next' }
			</button>
		</div>
	);
};

const baseRoutes = [
	{
		key: 'first',
		container: (
			<ContainerWithNextButton nextKey="second">
				One
			</ContainerWithNextButton>
		),
		label: 'First',
		isComplete: false,
	},
	{
		key: 'second',
		container: (
			<ContainerWithNextButton nextKey="third">
				Two
			</ContainerWithNextButton>
		),
		label: 'Second',
		isComplete: false,
	},
	{
		key: 'third',
		container: (
			<ContainerWithNextButton nextKey="first">
				Three
			</ContainerWithNextButton>
		),
		label: 'Third',
		isComplete: false,
	},
];

describe( '<QueryStringRouter />', () => {
	it( 'Should render the first route', () => {
		const { queryByText } = render(
			<QueryStringRouter routes={ baseRoutes } queryStringName="route">
				<ContentContainer />
			</QueryStringRouter>
		);

		expect( queryByText( 'One' ) ).toBeTruthy();
	} );

	it( 'Should navigate to the next route', () => {
		const { queryByText, queryByTestId } = render(
			<QueryStringRouter routes={ baseRoutes } queryStringName="route">
				<ContentContainer />
			</QueryStringRouter>
		);

		fireEvent.click( queryByTestId( 'next-button' ) );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );

	it( 'Should go to the correct route after on popstate', () => {
		const { queryByText, queryByTestId } = render(
			<QueryStringRouter routes={ baseRoutes } queryStringName="route">
				<ContentContainer />
			</QueryStringRouter>
		);

		mockSearch( 'route=second' );
		fireEvent.popState( window );

		expect( queryByText( 'Two' ) ).toBeTruthy();
	} );
} );
