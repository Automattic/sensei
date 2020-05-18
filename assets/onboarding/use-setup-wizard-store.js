import { useSelect, useDispatch } from '@wordpress/data';

/**
 *
 * @typedef {Object} OnboardingApiHandle
 *
 * @property {boolean}          isSubmitting Submitting state.
 * @property {Object}           stepData     API response from GET call to endpoint.
 * @property {Object}           error        Submit error.
 * @property {function(Object)} submitStep   Method to POST to endpoint.
 */
/**
 * Use Onboarding REST API for the given step.
 *
 * Loads data via the GET method for the endpoint, and provides a submit function that sends data to the endpoint
 * via POST request.
 *
 * @param {string} step Onboarding step endpoint name.
 * @return {OnboardingApiHandle} handle
 */
export const useSetupWizardStore = ( step ) => {
	const { stepData, isSubmitting, error } = useSelect(
		( select ) => ( {
			stepData: select( 'sensei/setup-wizard' ).getStepData( step ),
			isSubmitting: select( 'sensei/setup-wizard' ).isSubmitting(),
			error: select( 'sensei/setup-wizard' ).getSubmitError(),
		} ),
		[]
	);
	const { submitStep } = useDispatch( 'sensei/setup-wizard' );

	return {
		stepData,
		submitStep: submitStep.bind( null, step ),
		isSubmitting,
		error,
	};
};
