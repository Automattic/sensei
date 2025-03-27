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

	it( 'Submit is disabled until an item is selected and details filled out (if provided)', async () => {
		const { getByLabelText, getByPlaceholderText } = render(
			<ExitSurveyForm />
		);

		expect( buttons.submit() ).toBeDisabled();

		// This reason does not require details.
		await userEvent.click(
			getByLabelText( 'I no longer need the plugin' )
		);
		expect( buttons.submit() ).not.toBeDisabled();

		// This reason does expect details.
		await userEvent.click( getByLabelText( 'I found a better plugin' ) );
		expect( buttons.submit() ).toBeDisabled();
		await userEvent.type(
			getByPlaceholderText( "What's the name of the plugin?" ),
			'Test detail'
		);

		expect( buttons.submit() ).not.toBeDisabled();
	} );

	it( 'Skip button skips submission', async () => {
		const skip = jest.fn();
		const submit = jest.fn();
		render( <ExitSurveyForm submit={ submit } skip={ skip } /> );
		await userEvent.click( buttons.skip() );

		expect( skip ).toHaveBeenCalled();
		expect( submit ).not.toHaveBeenCalled();
	} );

	it( 'Submits selected reason and details', async () => {
		const submit = jest.fn();
		const { getByLabelText, getByPlaceholderText } = render(
			<ExitSurveyForm submit={ submit } />
		);

		await userEvent.click( getByLabelText( 'I found a better plugin' ) );
		await userEvent.type(
			getByPlaceholderText( "What's the name of the plugin?" ),
			'Test detail'
		);
		await userEvent.click( buttons.submit() );

		expect( submit ).toHaveBeenCalledWith( {
			reason: 'found-better-plugin',
			details: 'Test detail',
		} );
	} );
} );
