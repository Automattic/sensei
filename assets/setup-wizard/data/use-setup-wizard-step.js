/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';

/**
 * @typedef {Object} StepStoreHookHandle
 *
 * @property {boolean}  isSubmitting Submitting state.
 * @property {Object}   stepData     Data for the step.
 * @property {Object}   error        Submit error.
 * @property {Element}  errorNotice  Error notice element.
 * @property {Function} submitStep   Method to POST to endpoint.
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
export const useSetupWizardStep = ( step ) => {
	const { stepData, isSubmitting, error, isComplete } = useSelect(
		( select ) => ( {
			stepData: select( 'sensei/setup-wizard' ).getStepData( step ),
			isSubmitting: select( 'sensei/setup-wizard' ).isSubmitting(),
			error: select( 'sensei/setup-wizard' ).getSubmitError(),
			isComplete: select( 'sensei/setup-wizard' ).isCompleteStep( step ),
		} ),
		[]
	);
	const { submitStep } = useDispatch( 'sensei/setup-wizard' );

	const errorNotice = error ? (
		<Notice
			status="error"
			className="sensei-setup-wizard__submit-error"
			isDismissible={ false }
		>
			{ error.message }
		</Notice>
	) : null;

	const submitStepForComponent = useCallback(
		( formData, options ) => submitStep( step, formData, options ),
		[ step, submitStep ]
	);

	return {
		stepData,
		submitStep: submitStepForComponent,
		isSubmitting,
		error,
		errorNotice,
		isComplete,
	};
};
