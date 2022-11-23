/**
 * Internal dependencies
 */
import {
	API_SPECIAL_ACTIVE_JOB_ID,
	FETCH_FROM_API,
	START_LOAD_CURRENT_JOB_STATE,
	SUCCESS_LOAD_CURRENT_JOB_STATE,
	ERROR_LOAD_CURRENT_JOB_STATE,
	START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	START_DELETE_IMPORT_DATA_FILE,
	SUCCESS_DELETE_IMPORT_DATA_FILE,
	ERROR_DELETE_IMPORT_DATA_FILE,
	RESET_STATE,
	SET_JOB_STATE,
	WAIT,
} from './constants';
import { composeFetchAction } from '../../../shared/data/store-helpers';
import { normalizeImportData } from './normalizer';
import { buildJobEndpointUrl } from '../helpers/url';

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

export const wait = ( timeout ) => ( {
	type: WAIT,
	timeout,
} );

/**
 * Fetch importer data for current job.
 */
export const loadCurrentJobState = composeFetchAction(
	START_LOAD_CURRENT_JOB_STATE,
	function* () {
		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( API_SPECIAL_ACTIVE_JOB_ID ),
		} );

		return normalizeImportData( data );
	},
	SUCCESS_LOAD_CURRENT_JOB_STATE,
	ERROR_LOAD_CURRENT_JOB_STATE
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
 * Run job batches and query progress until it is completed.
 *
 * @param {string} jobId Job ID.
 */
export const pollJobProgress = function* ( jobId ) {
	try {
		const job = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'process' ] ),
			method: 'POST',
		} );

		yield setJobState( normalizeImportData( job ) );

		const { status } = job.status;
		if ( status !== 'completed' ) {
			yield* pollJobProgress( jobId );
		}
	} catch ( err ) {
		yield wait( 2000 );
		yield* pollJobProgress( jobId );
	}
};

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
 * @property {string}         type  Action type.
 * @property {Object|boolean} error Error object or false.
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
 * @param {string}   jobId               The job identifier.
 * @param {string}   level               Level identifier.
 * @param {Object}   uploadData          Data to submit.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] Callback on success.
 * @param {Function} [options.onError]   Callback on error.
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
 * @property {string} type       Action type.
 * @property {string} level      Level identifier.
 * @property {Object} uploadData Error object or false.
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
 * @property {string} type  Action type.
 * @property {string} level Level identifier.
 * @property {Object} data  Data object.
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
 * @property {string}         type  Action type.
 * @property {string}         level Level identifier.
 * @property {Object|boolean} error Error object or false.
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
 * Delete a level file.
 *
 * @param {string} jobId The job identifier.
 * @param {string} level Level identifier.
 */
export function* deleteLevelFile( jobId, level ) {
	yield startDeleteLevelFileAction( level );

	try {
		if ( ! jobId ) {
			yield errorDeleteLevelFileAction( {
				message: null, // Internal error. No actionable message to user.
			} );

			return;
		}

		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'file', level ] ),
			method: 'DELETE',
		} );

		yield successDeleteLevelFileAction(
			level,
			normalizeImportData( data )
		);
	} catch ( error ) {
		yield errorDeleteLevelFileAction( level, error );
	}
}

/**
 * @typedef  {Object} StartDeleteLevelFileAction
 * @property {string} type  Action type.
 * @property {string} level Level identifier.
 */
/**
 * Start file upload action creator.
 *
 * @param {string} level Level identifier.
 *
 * @return {StartDeleteLevelFileAction} Start delete file action.
 */
export const startDeleteLevelFileAction = ( level ) => ( {
	type: START_DELETE_IMPORT_DATA_FILE,
	level,
} );

/**
 * @typedef  {Object} SuccessDeleteLevelFileAction
 * @property {string} type  Action type.
 * @property {string} level Level identifier.
 * @property {Object} data  Data object.
 */
/**
 * Success delete level file action.
 *
 * @param {string} level Level identifier.
 * @param {Object} data  Importer data.
 * @return {SuccessDeleteLevelFileAction} Success delete level file action.
 */
export const successDeleteLevelFileAction = ( level, data ) => ( {
	type: SUCCESS_DELETE_IMPORT_DATA_FILE,
	level,
	data,
} );

/**
 * @typedef  {Object}  ErrorSuccessDeleteLevelFileAction
 * @property {string} type  Action type.
 * @property {string} level Level identifier.
 * @property {Object} error Error object or false.
 */
/**
 * Error delete level file action creator.
 *
 * @param {string} level Level identifier.
 * @param {Object} error Error object or false.
 *
 * @return {ErrorSuccessDeleteLevelFileAction} Error delete level file action.
 */
export const errorDeleteLevelFileAction = ( level, error ) => ( {
	type: ERROR_DELETE_IMPORT_DATA_FILE,
	level,
	error,
} );

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
	yield loadCurrentJobState();
}
