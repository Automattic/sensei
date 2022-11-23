/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { store as setupWizardStore } from './index';

/**
 * @typedef {Object} StepStoreHookHandle
 *
 * @property {Object}   stepData     Data for the step.
 * @property {Function} submitStep   Method to POST to endpoint.
 * @property {boolean}  isSubmitting Submitting state.
 * @property {Object}   error        Submit error.
 * @property {Element}  errorNotice  Error notice element.
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
	const { stepData, isSubmitting, error } = useSelect( ( select ) => {
		const store = select( setupWizardStore );

		return {
			stepData: store.getStepData( step ),
			isSubmitting: store.isSubmitting(),
			error: store.getSubmitError(),
		};
	}, [] );

	const { submitStep } = useDispatch( setupWizardStore );

	const errorNotice = error ? (
		<Notice
			status="error"
			className="sensei-setup-wizard__error-notice"
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
	};
};
