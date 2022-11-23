/**
 * WordPress dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	API_BASE_PATH,
	START_FETCH_SETUP_WIZARD_DATA,
	SUCCESS_FETCH_SETUP_WIZARD_DATA,
	ERROR_FETCH_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_DATA,
} from './constants';

/**
 * Fetch setup wizard data action creator.
 */
export function* fetchSetupWizardData() {
	yield startFetch();

	try {
		const data = yield apiFetch( {
			path: API_BASE_PATH.replace( /\/$/, '' ),
		} );
		yield successFetch( data );
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
 * @return {{type: string}} Start submit action.
 */
export const startSubmit = () => ( {
	type: START_SUBMIT_SETUP_WIZARD_DATA,
} );

/**
 * Success submit action creator.
 *
 * @return {{type: string}} Success submit action.
 */
export const successSubmit = () => ( {
	type: SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
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
 * @param {Object}   data                Data to submit.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] Step name.
 * @param {Function} [options.onError]   Data to submit.
 */
export function* submitStep( step, data, { onSuccess, onError } = {} ) {
	yield startSubmit();

	try {
		yield apiFetch( {
			path: API_BASE_PATH + step,
			method: 'POST',
			data,
		} );
		yield successSubmit();
		yield setData( data );

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
 * @typedef  {Object} SetDataAction
 * @property {string} type Action type.
 * @property {Object} data Data object.
 */
/**
 * Set data action creator.
 *
 * @param {Object} data Data object.
 *
 * @return {SetDataAction} Set data action.
 */
export const setData = ( data ) => ( {
	type: SET_DATA,
	data,
} );
