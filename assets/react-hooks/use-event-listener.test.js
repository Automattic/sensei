import { render, fireEvent } from '@testing-library/react';

import useEventListener from './use-event-listener';

describe( 'useEventListener', () => {
	it( 'Should add event listener to the window and remove when unmounted', () => {
		const eventHandlerMock = jest.fn();

		const TestComponent = () => {
			useEventListener( 'scroll', eventHandlerMock, [] );

			return <div />;
		};

		const { unmount } = render( <TestComponent /> );

		fireEvent.scroll( global );
		expect( eventHandlerMock ).toBeCalled();

		eventHandlerMock.mockReset();
		unmount();

		fireEvent.scroll( global );
		expect( eventHandlerMock ).not.toBeCalled();
	} );

	it( 'Should add event listener to an specific element', () => {
		const eventHandlerMock = jest.fn();

		// Create external test element.
		const button = document.createElement( 'button' );
		button.setAttribute( 'id', 'test-button' );
		document.body.appendChild( button );

		const TestComponent = () => {
			useEventListener(
				'click',
				eventHandlerMock,
				[],
				document.getElementById( 'test-button' )
			);

			return <div />;
		};

		render( <TestComponent /> );

		fireEvent.click( document.getElementById( 'test-button' ) );
		expect( eventHandlerMock ).toBeCalled();
	} );
} );
