/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { ExitSurveyForm } from './form';

describe( '<ExitSurveyForm />', () => {
	beforeEach( () => {} );

	const buttons = {
		submit: () => screen.getByRole( 'button', { name: 'Submit Feedback' } ),
		skip: () => screen.getByRole( 'button', { name: 'Skip Feedback' } ),
	};

	it( 'Submit is disabled until an item is selected and details filled out (if provided)', () => {
		const { getByLabelText, getByPlaceholderText } = render(
			<ExitSurveyForm />
		);

		expect( buttons.submit() ).toBeDisabled();

		// This reason does not require details.
		userEvent.click( getByLabelText( 'I no longer need the plugin' ) );
		expect( buttons.submit() ).not.toBeDisabled();

		// This reason does expect details.
		userEvent.click( getByLabelText( 'I found a better plugin' ) );
		expect( buttons.submit() ).toBeDisabled();
		userEvent.type(
			getByPlaceholderText( "What's the name of the plugin?" ),
			'Test detail'
		);

		expect( buttons.submit() ).not.toBeDisabled();
	} );

	it( 'Skip button skips submission', () => {
		const skip = jest.fn();
		const submit = jest.fn();
		render( <ExitSurveyForm submit={ submit } skip={ skip } /> );
		userEvent.click( buttons.skip() );

		expect( skip ).toHaveBeenCalled();
		expect( submit ).not.toHaveBeenCalled();
	} );

	it( 'Submits selected reason and details', () => {
		const submit = jest.fn();
		const { getByLabelText, getByPlaceholderText } = render(
			<ExitSurveyForm submit={ submit } />
		);

		userEvent.click( getByLabelText( 'I found a better plugin' ) );
		userEvent.type(
			getByPlaceholderText( "What's the name of the plugin?" ),
			'Test detail'
		);
		userEvent.click( buttons.submit() );

		expect( submit ).toHaveBeenCalledWith( {
			reason: 'found-better-plugin',
			details: 'Test detail',
		} );
	} );
} );
