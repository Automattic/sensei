/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import SignupForm from './signup-form';

const stepData = {
	admin_email: 'admin@test.local',
	mc_url: 'http://external.local/campaign',
	gdpr_field: 'SDCTZX3',
};

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
		delete window.location;
		window.location = { assign: jest.fn() };

		mockStepData();
	} );

	afterEach( () => {
		delete window.sensei_log_event;
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
		expect( form.getAttribute( 'action' ) ).toEqual( stepData.mc_url );
	} );

	it( 'Should have the admin e-mail pre-filled in the sign-up form', () => {
		const { container } = render( <SignupForm /> );

		const form = container.querySelector( 'form' );
		expect( form.querySelector( 'input[type=email]' ).value ).toEqual(
			stepData.admin_email
		);
	} );
} );
