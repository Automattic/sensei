import { render, fireEvent } from '@testing-library/react';
import { logLink } from './log-event';

describe( 'logOnClick', () => {
	it( 'Calls sensei_log_event on click.', () => {
		window.sensei_log_event = jest.fn();

		const { getByText } = render(
			<a href="/" { ...logLink( 'test-event', { prop: 'test' } ) }>
				Logged link
			</a>
		);

		fireEvent.click( getByText( 'Logged link' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith( 'test-event', {
			prop: 'test',
		} );
	} );
} );
