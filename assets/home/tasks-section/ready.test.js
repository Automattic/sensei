/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import nock from 'nock';

/**
 * Internal dependencies
 */
import Ready from './ready';

describe( '<Ready />', () => {
	it( 'Should render the correct share links', async () => {
		const { queryByText } = render(
			<Ready coursePermalink="http://my-site/my-course" />
		);

		expect(
			queryByText( 'Facebook' ).parentNode.getAttribute( 'href' )
		).toEqual(
			'https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fmy-site%2Fmy-course'
		);

		expect(
			queryByText( 'Twitter' ).parentNode.getAttribute( 'href' )
		).toEqual(
			'https://twitter.com/intent/tweet?text=My new course is ready! Check it here: http%3A%2F%2Fmy-site%2Fmy-course'
		);

		expect(
			queryByText( 'Tumblr' ).parentNode.getAttribute( 'href' )
		).toEqual(
			'https://www.tumblr.com/widgets/share/tool?posttype=link&caption=My new course is ready!&content=http%3A%2F%2Fmy-site%2Fmy-course&canonicalUrl=http%3A%2F%2Fmy-site%2Fmy-course'
		);
	} );

	it( 'Should call dismiss callback when clicking on dismiss', async () => {
		const onDismissMock = jest.fn();

		window.sensei_home = {
			dismiss_tasks_nonce: 'nonce',
		};
		window.ajaxurl = '/';

		const scope = nock( 'http://localhost' ).post( '/' ).reply( 200, {} );

		const { container } = render(
			<Ready
				coursePermalink="http://my-site/my-course"
				onDismiss={ onDismissMock }
			/>
		);

		fireEvent.click(
			container.querySelector( '.sensei-home-ready__dismiss' )
		);

		await waitFor( () => {
			expect( onDismissMock ).toBeCalled();
			scope.done();
		} );
	} );
} );
