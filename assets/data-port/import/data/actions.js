import {
	API_SPECIAL_ACTIVE_JOB_ID,
	FETCH_FROM_API,
	START_FETCH_CURRENT_JOB_STATE,
	SUCCESS_FETCH_CURRENT_JOB_STATE,
	ERROR_FETCH_CURRENT_JOB_STATE,
	START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	RESET_STATE,
	SET_JOB_STATE,
	SUCCESS_FETCH_IMPORT_LOG,
	START_FETCH_IMPORT_LOG,
	ERROR_FETCH_IMPORT_LOG,
} from './constants';

import { composeFetchAction } from '../../../shared/data/store-helpers';
import { normalizeImportData } from './normalizer';
import { buildJobEndpointUrl } from '../helpers/url';

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
export const fetchCurrentJobState = composeFetchAction(
	START_FETCH_CURRENT_JOB_STATE,
	function*() {
		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( API_SPECIAL_ACTIVE_JOB_ID ),
		} );

		return normalizeImportData( data );
	},
	SUCCESS_FETCH_CURRENT_JOB_STATE,
	ERROR_FETCH_CURRENT_JOB_STATE
);

/**
 * Update job state in the background.
 *
 * @param {string} jobId The job ID.
 */
export function* updateJobState( jobId ) {
	try {
		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId ),
		} );

		yield setJobState( normalizeImportData( data ) );
	} catch ( error ) {
		// Silent.
	}
}

/**
 * @typedef  {Object} SetJobStateAction
 * @property {string} type Action type.
 * @property {Object} data Job state.
 */
/**
 * Set job state action creator.
 *
 * @param {Object} data Job state.
 * @return {SetJobStateAction} Set job state action.
 */
export const setJobState = ( data ) => ( {
	type: SET_JOB_STATE,
	data,
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
		if ( ! jobId ) {
			yield errorStartImport( {
				message: null, // Internal error. No actionable message to user.
			} );

			return;
		}

		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'start' ] ),
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
 * @param {Object} error Error object or false.
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
			path: buildJobEndpointUrl( jobId, [ 'file', level ] ),
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
 * Load the import log for the given job.
 *
 * @param {string} jobId The job id.
 */
export const fetchImportLog = composeFetchAction(
	START_FETCH_IMPORT_LOG,
	function*( jobId ) {
		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'logs' ] ),
		} );

		return data;
	},
	SUCCESS_FETCH_IMPORT_LOG,
	ERROR_FETCH_IMPORT_LOG
);

/**
 * Reset importer state.
 */
export const resetState = () => ( {
	type: RESET_STATE,
} );

/**
 * Restart importer.
 */
export function* restartImporter() {
	yield resetState();
	yield fetchCurrentJobState();
}
