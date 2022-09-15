/**
 * External dependencies
 */
import { fireEvent, render, act, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import Ready from './index';

const stepData = {
	admin_email: 'admin@test.local',
	mc_url: 'http://external.local/campaign',
	gdpr_field: 'SDCTZX3',
};

// Mock features data.
jest.mock( '../data/use-setup-wizard-step', () => ( {
	useSetupWizardStep: jest.fn(),
} ) );

// Mock apiFetch.
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// Mock features data.
const mockStepData = ( mockData = {} ) => {
	useSetupWizardStep.mockReturnValue( {
		stepData,
		isComplete: false,
		submitStep: () => {},
		...mockData,
	} );
};

describe( '<Ready />', () => {
	beforeEach( () => {
		window.sensei_log_event = jest.fn();
		delete window.location;
		window.location = { assign: jest.fn() };

		mockStepData();
	} );

	afterEach( () => {
		delete window.sensei_log_event;
	} );

	it( 'Should have a sign-up form pointing to the mailing list provider', () => {
		const { container } = render( <Ready /> );

		const form = container.querySelector( 'form' );
		expect( form.getAttribute( 'action' ) ).toEqual( stepData.mc_url );
	} );

	it( 'Should have the admin e-mail pre-filled in the sign-up form', () => {
		const { container } = render( <Ready /> );

		const form = container.querySelector( 'form' );
		expect( form.querySelector( 'input[type=email]' ).value ).toEqual(
			stepData.admin_email
		);
	} );

	it.skip( 'Should have a create course button', () => {
		const { queryByText } = render( <Ready /> );

		expect(
			queryByText( 'Create a course' ).getAttribute( 'href' )
		).toEqual( 'post-new.php?post_type=course' );
	} );

	it.skip( 'Should have an import content button', () => {
		const { queryByText } = render( <Ready /> );
		expect(
			queryByText( 'Import content', {
				selector: 'a',
			} ).getAttribute( 'href' )
		).toEqual(
			'edit.php?post_type=course&page=sensei-tools&tool=import-content'
		);
	} );

	it.skip( 'Should have a create your first course link.', () => {
		const { queryByText } = render( <Ready /> );

		expect(
			queryByText( /create your first course/ ).getAttribute( 'href' )
		).toEqual( 'https://senseilms.com/lesson/courses/' );
	} );

	it.skip( 'Should have a install sample course button', () => {
		const { queryByText } = render( <Ready /> );

		expect(
			queryByText( 'Install a sample course', { selector: 'button' } )
		).toBeTruthy();
	} );

	it( 'Should log event when clicking to join mailing list', () => {
		const { queryByText } = render( <Ready /> );
		const button = queryByText( 'Yes, please!' );

		// Temporarily set button type to "button" to prevent form submission.
		button.setAttribute( 'type', 'button' );
		fireEvent.click( button );
		button.setAttribute( 'type', 'submit' );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_mailing_list',
			undefined
		);
	} );

	it.skip( 'Should log event when clicking "Create a Course" button', () => {
		const { queryByText } = render( <Ready /> );

		const element = queryByText( 'Create a course', { selector: 'a' } );

		element.setAttribute( 'href', '#' ); // Temporarily rewrite the `href` attribute to avoid warnings

		fireEvent.click( element );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_create_course',
			undefined
		);
	} );

	it.skip( 'Should log event when clicking to import content button', async () => {
		const { queryByText } = render( <Ready /> );

		const element = queryByText( 'Import content', { selector: 'a' } );

		element.setAttribute( 'href', '#' ); // Temporarily rewrite the `href` attribute to avoid warnings
		fireEvent.click( element );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_import',
			undefined
		);
	} );

	it.skip( 'Should log event when clicking to install sample course', async () => {
		const fetchPromise = Promise.resolve( { id: 1 } );
		apiFetch.mockImplementation( () => fetchPromise );

		const { queryByText } = render( <Ready /> );

		fireEvent.click(
			queryByText( 'Install a sample course', { selector: 'button' } )
		);

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_install_course',
			undefined
		);

		await act( () => fetchPromise );
	} );

	it.skip( 'Should log event when clicking to create the first course', () => {
		const { queryByText } = render( <Ready /> );

		fireEvent.click( queryByText( 'create your first course.' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_learn_more',
			undefined
		);
	} );

	it( 'Should log event when clicking to exit', () => {
		const { queryByText } = render( <Ready /> );

		const element = queryByText( 'Exit to Courses', { selector: 'a' } );

		element.setAttribute( 'href', '#' ); // Temporarily rewrite the `href` attribute to avoid warnings
		fireEvent.click( element );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_ready_exit',
			undefined
		);
	} );

	it( 'Should submit the ready step', () => {
		const submitMock = jest.fn();

		mockStepData( {
			isComplete: false,
			submitStep: submitMock,
		} );

		render( <Ready /> );

		expect( submitMock ).toBeCalled();
	} );

	it( 'Should not submit the ready step when it is already complete', () => {
		const submitMock = jest.fn();

		mockStepData( {
			isComplete: true,
			submitStep: submitMock,
		} );

		render( <Ready /> );

		expect( submitMock ).not.toBeCalled();
	} );

	it.skip( 'Should run sample installation', async () => {
		window.sensei_setup_wizard = { nonce: '123' };

		apiFetch.mockResolvedValueOnce( { id: 1 } );
		apiFetch.mockResolvedValueOnce( {
			status: { status: 'pending' },
		} );
		apiFetch.mockResolvedValueOnce( {
			status: { status: 'completed' },
		} );

		const { queryByText } = render( <Ready /> );

		fireEvent.click(
			queryByText( 'Install a sample course', { selector: 'button' } )
		);

		await waitFor( () => {
			expect( window.location.assign ).toBeCalledWith(
				'?redirect_imported_sample=1&job_id=1&nonce=123'
			);
		} );
	} );
} );
