import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_SETUP_WIZARD_DATA,
	SUCCESS_FETCH_SETUP_WIZARD_DATA,
	ERROR_FETCH_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_WELCOME_STEP_DATA,
} from './constants';

/**
 * @typedef  {Object} FetchFromAPIAction
 * @property {string} type               Action type.
 * @property {Object} request            Object that is used to fetch.
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

	// TODO: Refactory to get a single endpoint with all data.
	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH,
		} );
		yield successFetch( {
			welcome: {
				...data,
			},
		} );
	} catch ( error ) {
		yield errorFetch( error );
	}
}

/**
 * @typedef  {Object} SuccessSetupWizardDataAction
 * @property {string} type                         Action type.
 * @property {Object} data                         Setup wizard data.
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
 * @property {string}         type             Action type.
 * @property {Object|boolean} error            Error object or false.
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
 * @property {string}         type              Action type.
 * @property {Object|boolean} error             Error object or false.
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
 * Submit welcome step action creator.
 *
 * @param {boolean} usageTracking Usage tracking.
 */
export function* submitWelcomeStep( usageTracking ) {
	const welcomeStepData = { usage_tracking: usageTracking };
	yield startSubmit();

	try {
		yield fetchFromAPI( {
			path: API_BASE_PATH + 'welcome',
			method: 'POST',
			data: welcomeStepData,
		} );
		yield successSubmit();
		yield setWelcomeStepData( welcomeStepData );
	} catch ( error ) {
		yield errorSubmit( error );
	}
}

/**
 * @typedef  {Object} SetWelcomeStepDataAction
 * @property {string} type                     Action type.
 * @property {Object} data                     Welcome step data.
 */
/**
 * Set welcome step data action creator.
 *
 * @param {Object} data Welcome data object.
 *
 * @return {SetWelcomeStepDataAction} Set welcome step data action.
 */
export const setWelcomeStepData = ( data ) => ( {
	type: SET_WELCOME_STEP_DATA,
	data,
} );
