/**
 * Internal dependencies
 */
import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_SETUP_WIZARD_DATA,
	SUCCESS_FETCH_SETUP_WIZARD_DATA,
	ERROR_FETCH_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_STEP_DATA,
	APPLY_STEP_DATA,
} from './constants';
import { normalizeSetupWizardData } from './normalizer';

/**
 * @typedef  {Object} FetchFromAPIAction
 * @property {string} type    Action type.
 * @property {Object} request Object that is used to fetch.
 */
/**
 * Fetch action creator.
 *
 * @param {Object} request Object that is used to fetch.
 *
 * @return {FetchFromAPIAction} Fetch action.
 */
export const fetchFromAPI = ( request ) => ( {
	type: FETCH_FROM_API,
	request,
} );

/**
 * Fetch setup wizard data action creator.
 */
export function* fetchSetupWizardData() {
	yield startFetch();

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH.replace( /\/$/, '' ),
		} );
		yield successFetch( normalizeSetupWizardData( data ) );
	} catch ( error ) {
		yield errorFetch( error );
	}
}

/**
 * @typedef  {Object} SuccessSetupWizardDataAction
 * @property {string} type Action type.
 * @property {Object} data Setup wizard data.
 */
/**
 * Success fetch action creator.
 *
 * @param {Object} data Setup wizard data.
 *
 * @return {SuccessSetupWizardDataAction} Success fetch action.
 */
export const successFetch = ( data ) => ( {
	type: SUCCESS_FETCH_SETUP_WIZARD_DATA,
	data,
} );

/**
 * @typedef  {Object}         ErrorFetchAction
 * @property {string}         type  Action type.
 * @property {Object|boolean} error Error object or false.
 */
/**
 * Error fetch action creator.
 *
 * @param {Object|boolean} error Error object or false.
 *
 * @return {ErrorFetchAction} Error action.
 */
export const errorFetch = ( error ) => ( {
	type: ERROR_FETCH_SETUP_WIZARD_DATA,
	error,
} );

/**
 * Start fetch setup wizard data action creator.
 *
 * @return {{type: string}} Start fetch action.
 */
export const startFetch = () => ( {
	type: START_FETCH_SETUP_WIZARD_DATA,
} );

/**
 * Start submit action creator.
 *
 * @param {string} step     Step name.
 * @param {Object} stepData Data to submit.
 *
 * @return {{type: string}} Start submit action.
 */
export const startSubmit = ( step, stepData ) => ( {
	type: START_SUBMIT_SETUP_WIZARD_DATA,
	step,
	stepData,
} );

/**
 * Success submit action creator.
 *
 * @param {string} step Completed step.
 * @return {{type: string, step: string}} Success submit action.
 */
export const successSubmit = ( step ) => ( {
	type: SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	step,
} );

/**
 * @typedef  {Object}         ErrorSubmitAction
 * @property {string}         type  Action type.
 * @property {Object|boolean} error Error object or false.
 */
/**
 * Error submit action creator.
 *
 * @param {Object|boolean} error Error object or false.
 *
 * @return {ErrorSubmitAction} Error action.
 */
export const errorSubmit = ( error ) => ( {
	type: ERROR_SUBMIT_SETUP_WIZARD_DATA,
	error,
} );

/**
 * Submit step action creator.
 *
 * @param {string}   step                Step name.
 * @param {Object}   stepData            Data to submit.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] Step name.
 * @param {Function} [options.onError]   Data to submit.
 */
export function* submitStep( step, stepData, { onSuccess, onError } = {} ) {
	yield startSubmit( step, stepData );

	try {
		yield fetchFromAPI( {
			path: API_BASE_PATH + step,
			method: 'POST',
			data: stepData,
		} );
		yield successSubmit( step );
		yield applyStepData( step, stepData );
		yield setStepData( step, stepData );

		if ( onSuccess ) {
			onSuccess();
		}
	} catch ( error ) {
		yield errorSubmit( error );

		if ( onError ) {
			onError( error );
		}
	}
}

/**
 * @typedef  {Object} SetStepDataAction
 * @property {string} type Action type.
 * @property {string} step Step name.
 * @property {Object} data Step data.
 */
/**
 * Set welcome step data action creator.
 *
 * @param {string} step Step name.
 * @param {Object} data Step data object.
 *
 * @return {SetStepDataAction} Set welcome step data action.
 */
export const setStepData = ( step, data ) => ( {
	type: SET_STEP_DATA,
	step,
	data,
} );

/**
 * Apply side-effects for data change.
 *
 * @param {string} step Step name.
 * @param {Object} data Step data object.
 */
export const applyStepData = ( step, data ) => ( {
	type: APPLY_STEP_DATA,
	step,
	data,
} );
