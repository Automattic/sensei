import { useSelect, useDispatch } from '@wordpress/data';

/**
 *
 * @typedef {Object} StepStoreHookHandle
 *
 * @property {boolean}          isSubmitting Submitting state.
 * @property {Object}           stepData     API response from GET call to endpoint.
 * @property {Object}           error        Submit error.
 * @property {function(Object)} submitStep   Method to POST to endpoint.
 */
/**
 * Use Setup Wizard State store and REST API for the given step.
 *
 * Gets step-specific data, and provides a submit function that sends step form data to the step endpoint
 * via POST request.
 *
 * @param {string} step Setup Wizard step endpoint name.
 * @return {StepStoreHookHandle} handle
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
