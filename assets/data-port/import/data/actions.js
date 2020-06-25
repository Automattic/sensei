import {
	API_BASE_PATH,
	API_SPECIAL_ACTIVE_JOB_ID,
	FETCH_FROM_API,
	START_GET_CURRENT_JOB_STATE,
	SUCCESS_GET_CURRENT_JOB_STATE,
	ERROR_GET_CURRENT_JOB_STATE,
	START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	SET_STEP_DATA,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
} from './constants';

import { normalizeImportData } from './normalizer';

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
 * Fetch importer data for current job.
 */
export function* getCurrentJobState() {
	yield startGetCurrentJobState();

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH + API_SPECIAL_ACTIVE_JOB_ID,
		} );

		yield successGetCurrentJobState( normalizeImportData( data ) );
	} catch ( error ) {
		yield errorGetCurrentJobState( error );
	}
}

/**
 * @typedef  {Object} SuccessGetCurrentJobStateAction
 * @property {string} type                         Action type.
 * @property {Object} data                         Importer data.
 */
/**
 * Success get current job state action creator.
 *
 * @param {Object} data Importer data.
 *
 * @return {SuccessGetCurrentJobStateAction} Success get current job state action.
 */
export const successGetCurrentJobState = ( data ) => ( {
	type: SUCCESS_GET_CURRENT_JOB_STATE,
	data,
} );

/**
 * @typedef  {Object}         ErrorGetCurrentJobStateAction
 * @property {string}         type             Action type.
 * @property {Object|boolean} error            Error object or false.
 */
/**
 * Error get current job state action creator.
 *
 * @param {Object|boolean} error Error object or false.
 *
 * @return {ErrorGetCurrentJobStateAction} Error action.
 */
export const errorGetCurrentJobState = ( error ) => ( {
	type: ERROR_GET_CURRENT_JOB_STATE,
	error,
} );

/**
 * @typedef  {Object} StartGetCurrentJobStateAction
 * @property {string} type Action type.
 */
/**
 * Start get current job state action creator.
 *
 * @return {StartGetCurrentJobStateAction} Start get current job state action.
 */
export const startGetCurrentJobState = () => ( {
	type: START_GET_CURRENT_JOB_STATE,
} );

/**
 * Start import.
 *
 * @param {string}   jobId               The job identifier.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] On Success handler.
 * @param {Function} [options.onError]   On Error handler.
 */
export function* submitStartImport( jobId, { onSuccess, onError } = {} ) {
	yield startImport();

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH + jobId + '/start',
			method: 'POST',
		} );

		yield successStartImport( normalizeImportData( data ) );

		if ( onSuccess ) {
			onSuccess();
		}
	} catch ( error ) {
		yield errorStartImport( error );

		if ( onError ) {
			onError( error );
		}
	}
}

/**
 * @typedef  {Object} StartImportAction
 * @property {string} type Action type.
 */
/**
 * Start action to start import.
 *
 * @return {StartImportAction} Start import action.
 */
export const startImport = () => ( {
	type: START_IMPORT,
} );

/**
 * @typedef  {Object} SuccessStartImportAction
 * @property {string} type Action type.
 * @property {Object} data Data object.
 */
/**
 * Success submit action creator.
 *
 * @param {Object} data Importer data.
 * @return {SuccessStartImportAction} Success submit action.
 */
export const successStartImport = ( data ) => ( {
	type: SUCCESS_START_IMPORT,
	data,
} );

/**
 * @typedef  {Object}         ErrorStartImportAction
 * @property {string}         type   Action type.
 * @property {Object|boolean} error  Error object or false.
 */
/**
 * Error start import job creator.
 *
 * @param {Object|boolean} error Error object or false.
 *
 * @return {ErrorStartImportAction} Error action.
 */
export const errorStartImport = ( error ) => ( {
	type: ERROR_START_IMPORT,
	error,
} );

/**
 * Upload a file for a level.
 *
 * @param {string}   jobId                 The job identifier.
 * @param {string}   level                 Level identifier.
 * @param {Object}   uploadData            Data to submit.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess]   Step name.
 * @param {Function} [options.onError]     Data to submit.
 */
export function* uploadFileForLevel(
	jobId,
	level,
	uploadData,
	{ onSuccess, onError } = {}
) {
	yield startFileUploadAction( level, uploadData );

	try {
		if ( ! jobId ) {
			jobId = API_SPECIAL_ACTIVE_JOB_ID;
		}

		const data = yield fetchFromAPI( {
			path: API_BASE_PATH + jobId + '/file/' + level,
			method: 'POST',
			body: uploadData,
		} );

		yield successFileUpload( level, normalizeImportData( data ) );

		if ( onSuccess ) {
			onSuccess();
		}
	} catch ( error ) {
		yield errorFileUpload( level, error );

		if ( onError ) {
			onError( error );
		}
	}
}

/**
 * Throw an early upload error.
 *
 * @param {string} level    Level identifier.
 * @param {string} errorMsg Error object or false.
 */
export const throwEarlyUploadError = ( level, errorMsg ) =>
	errorFileUpload( level, {
		code: '',
		message: errorMsg,
	} );

/**
 * @typedef  {Object} StartFileUploadAction
 * @property {string} type        Action type.
 * @property {string} level       Level identifier.
 * @property {Object} uploadData  Error object or false.
 */
/**
 * Start file upload action creator.
 *
 * @param {string} level      Level identifier.
 * @param {Object} uploadData Data to submit.
 *
 * @return {StartFileUploadAction} Start file upload action.
 */
export const startFileUploadAction = ( level, uploadData ) => ( {
	type: START_UPLOAD_IMPORT_DATA_FILE,
	level,
	uploadData,
} );

/**
 * @typedef  {Object} SuccessFileUploadAction
 * @property {string} type    Action type.
 * @property {string} level   Level identifier.
 * @property {Object} data    Data object.
 */
/**
 * Success upload file action.
 *
 * @param {string} level Level identifier.
 * @param {Object} data  Importer data.
 * @return {SuccessFileUploadAction} Success file upload action.
 */
export const successFileUpload = ( level, data ) => ( {
	type: SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	level,
	data,
} );

/**
 * @typedef  {Object}         ErrorFileUploadAction
 * @property {string}         type              Action type.
 * @property {string}         level             Level identifier.
 * @property {Object|boolean} error             Error object or false.
 */
/**
 * Error submit action creator.
 *
 * @param {string}         level Level identifier.
 * @param {Object|boolean} error Error object or false.
 *
 * @return {ErrorFileUploadAction} Error action.
 */
export const errorFileUpload = ( level, error ) => ( {
	type: ERROR_UPLOAD_IMPORT_DATA_FILE,
	level,
	error,
} );

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
