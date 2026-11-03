/**
 * External dependencies
 */
import { fireEvent, render, waitFor, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import SignupForm from './signup-form';

const stepData = {
	admin_email: 'admin@test.local',
	newsletter_url:
		'https://external.local/wp-json/senseilms-newsletter/v1/subscribe',
};

const originalFetch = window.fetch;

// Mock features data.
jest.mock( '../data/use-setup-wizard-step', () => ( {
	useSetupWizardStep: jest.fn(),
} ) );

// Mock features data.
const mockStepData = ( mockData = {} ) => {
	useSetupWizardStep.mockReturnValue( {
		stepData,
		isComplete: false,
		submitStep: () => {},
		...mockData,
	} );
};

describe( '<SignupForm />', () => {
	beforeEach( () => {
		window.sensei_log_event = jest.fn();
		window.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: async () => ( { success: true } ),
		} );

		mockStepData();
	} );

	afterEach( () => {
		delete window.sensei_log_event;
		window.fetch = originalFetch;
	} );

	it( 'Should log event when clicking to join mailing list', () => {
		const { queryByText } = render( <SignupForm /> );
		const button = queryByText( 'Nice! Sign me up' );

		// Temporarily set button type to "button" to prevent form submission.
		button.setAttribute( 'type', 'button' );
		fireEvent.click( button );
		button.setAttribute( 'type', 'submit' );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_newsletter_signup',
			undefined
		);
	} );

	it( 'Should have a sign-up form pointing to the mailing list provider', () => {
		const { container } = render( <SignupForm /> );

		const form = container.querySelector( 'form' );
		expect( form.getAttribute( 'action' ) ).toEqual(
			stepData.newsletter_url
		);
	} );

	it( 'Should have the admin e-mail pre-filled in the sign-up form', () => {
		const { container } = render( <SignupForm /> );

		const form = container.querySelector( 'form' );
		expect( form.querySelector( 'input[type=email]' ).value ).toEqual(
			stepData.admin_email
		);
	} );

	it( 'Submits the selected email and explicit consent before continuing', async () => {
		const onSubmit = jest.fn();
		const { container } = render( <SignupForm onSubmit={ onSubmit } /> );
		fireEvent.change( container.querySelector( 'input[type=email]' ), {
			target: { value: 'subscriber@example.com' },
		} );

		fireEvent.submit( container.querySelector( 'form' ) );

		await waitFor( () => expect( onSubmit ).toHaveBeenCalledTimes( 1 ) );
		expect( window.fetch ).toHaveBeenCalledWith( stepData.newsletter_url, {
			method: 'POST',
			credentials: 'omit',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'email=subscriber%40example.com&consent=1',
		} );
	} );

	it.each( [
		[ 'server rejection', () => Promise.resolve( { ok: false } ) ],
		[
			'network failure',
			() => Promise.reject( new Error( 'Unavailable' ) ),
		],
		[
			'unexpected response',
			() => Promise.resolve( { ok: true, json: async () => ( {} ) } ),
		],
	] )(
		'Keeps the signup step available after a %s',
		async ( name, response ) => {
			const onSubmit = jest.fn();
			window.fetch.mockImplementation( response );
			const { container, getByText } = render(
				<SignupForm onSubmit={ onSubmit } />
			);

			fireEvent.submit( container.querySelector( 'form' ) );

			await within( container ).findByText(
				'Failed to connect to the server. Please try again later.'
			);
			expect( onSubmit ).not.toHaveBeenCalled();
			expect( getByText( 'Nice! Sign me up' ).disabled ).toBe( false );
		}
	);
} );
