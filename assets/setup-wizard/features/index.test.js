import { render, fireEvent } from '@testing-library/react';

import { mockSearch } from '../../tests-helper/functions';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import QueryStringRouter, { Route } from '../query-string-router';
import { updateQueryString } from '../query-string-router/url-functions';
import Features from './index';
import useFeaturesPolling from './use-features-polling';

// Mock features data.
jest.mock( '../data/use-setup-wizard-step', () => ( {
	useSetupWizardStep: jest.fn(),
} ) );

// Mock features data.
const mockStepData = ( mockData ) => {
	useSetupWizardStep.mockReturnValue( {
		stepData: mockData,
		submitStep: ( data, { onSuccess } = {} ) => {
			if ( onSuccess ) onSuccess();
		},
	} );
};

// Mock features polling.
jest.mock( './use-features-polling', () => jest.fn() );

describe( '<Features />', () => {
	beforeEach( () => {
		window.sensei_log_event = jest.fn();

		mockStepData( {
			selected: [],
			options: [
				{ slug: 'test-1', title: 'Test 1' },
				{ slug: 'test-2', title: 'Test 2' },
			],
		} );
	} );

	afterEach( () => {
		// Clear URL param.
		updateQueryString( 'step', '' );

		delete window.sensei_log_event;
	} );

	it( 'Should not check installed features', () => {
		mockStepData( {
			selected: [ 'installed' ],
			options: [
				{ slug: 'test-1', title: 'Test 1' },
				{ slug: 'installed', title: 'Test 2', status: 'installed' },
			],
		} );

		const { container } = render(
			<QueryStringRouter paramName="step">
				<Features />
			</QueryStringRouter>
		);

		expect( container.querySelector( 'input:checked' ) ).toBeFalsy();
	} );

	it( 'Should continue to the ready step when nothing is selected', () => {
		const { container, queryByText } = render(
			<QueryStringRouter paramName="step" defaultRoute="features">
				<Route route="features">
					<Features />
				</Route>
				<Route route="ready">Ready</Route>
			</QueryStringRouter>
		);

		fireEvent.click( queryByText( 'Continue' ) );

		expect( container.firstChild ).toMatchInlineSnapshot( 'Ready' );
	} );

	it( 'Should continue to the ready step when the user chooses to install later', () => {
		const { container, queryByText } = render(
			<QueryStringRouter paramName="step" defaultRoute="features">
				<Route route="features">
					<Features />
				</Route>
				<Route route="ready">Ready</Route>
			</QueryStringRouter>
		);

		// Check the first feature.
		fireEvent.click( container.querySelector( 'input[type="checkbox"]' ) );

		// Continue to confirmation.
		fireEvent.click( queryByText( 'Continue' ) );

		// Choose to install later.
		fireEvent.click( queryByText( "I'll do it later" ) );

		expect( container.firstChild ).toMatchInlineSnapshot( 'Ready' );
	} );

	it( 'Should continue to the confirmation and then installation feedback when some feature is selected', () => {
		useFeaturesPolling.mockReturnValue( {
			selected: [ 'test-1' ],
			options: [
				{ slug: 'test-1', title: 'Test 1', status: 'installed' },
			],
		} );

		const { container, queryByText, getByLabelText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		// Check the first feature.
		fireEvent.click( getByLabelText( 'Test 1' ) );

		// Continue to confirmation.
		fireEvent.click( queryByText( 'Continue' ) );

		// Start the installation.
		fireEvent.click( queryByText( 'Install now' ) );

		expect(
			container.querySelector( '.sensei-setup-wizard__icon-status' )
		).toBeTruthy();
	} );

	it( 'Should display installation error', () => {
		useFeaturesPolling.mockReturnValue( {
			selected: [ 'test-2' ],
			options: [ { slug: 'test-2', title: 'Test 2', status: 'error' } ],
		} );

		const { queryByText, getByLabelText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);
		fireEvent.click( getByLabelText( 'Test 2' ) );

		// Submit data.
		fireEvent.click( queryByText( 'Continue' ) );

		// Confirm the installation.
		fireEvent.click( queryByText( 'Install now' ) );

		expect( queryByText( 'Error installing plugin' ) ).toBeTruthy();
	} );

	it( 'Should log event on Continue when no features selected', () => {
		const { queryByText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		fireEvent.click( queryByText( 'Continue' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_continue',
			{
				slug: '',
			}
		);
	} );

	it( 'Should log events after installing features and continuing', () => {
		useFeaturesPolling.mockReturnValue( {
			selected: [ 'test-1', 'test-2' ],
			options: [
				{ slug: 'test-1', title: 'Test 1' },
				{ slug: 'test-2', title: 'Test 2' },
			],
		} );

		const { queryByText, getByLabelText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		// Check the two features.
		fireEvent.click( getByLabelText( 'Test 1' ) );
		fireEvent.click( getByLabelText( 'Test 2' ) );

		fireEvent.click( queryByText( 'Continue' ) );
		fireEvent.click( queryByText( 'Install now' ) );
		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_install',
			{
				slug: 'test-2,test-1',
			}
		);

		fireEvent.click( queryByText( 'Continue' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_continue',
			{
				slug: 'test-2,test-1',
			}
		);
	} );

	it( 'Should log installation skipping', () => {
		const { queryByText, getByLabelText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		// Check the first feature.
		fireEvent.click( getByLabelText( 'Test 1' ) );

		fireEvent.click( queryByText( 'Continue' ) );
		fireEvent.click( queryByText( "I'll do it later" ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_install_cancel',
			{
				slug: 'test-1',
			}
		);
	} );

	it( 'Should log installation retries', () => {
		useFeaturesPolling.mockReturnValue( {
			selected: [ 'test-1', 'test-2' ],
			options: [
				{ slug: 'test-1', title: 'Test 1', status: 'error' },
				{ slug: 'test-2', title: 'Test 2', status: 'error' },
			],
		} );

		const { queryByText, getByLabelText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);
		fireEvent.click( getByLabelText( 'Test 1' ) );
		fireEvent.click( getByLabelText( 'Test 2' ) );

		// Submit data.
		fireEvent.click( queryByText( 'Continue' ) );

		// Confirm the installation.
		fireEvent.click( queryByText( 'Install now' ) );

		// Retry installations.
		fireEvent.click( queryByText( 'Retry' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_install_retry',
			{
				slug: 'test-1,test-2',
			}
		);
	} );

	it( 'Should display installation feedback when feedback is an URL param', () => {
		mockSearch( 'feedback=1' );
		useFeaturesPolling.mockReturnValue( {
			selected: [ 'test' ],
			options: [ { slug: 'test', title: 'Test', status: 'installed' } ],
		} );

		const { queryByText } = render(
			<QueryStringRouter>
				<Features />
			</QueryStringRouter>
		);

		expect( queryByText( 'Plugin installed' ) ).toBeTruthy();
		mockSearch( '' );
	} );
} );
