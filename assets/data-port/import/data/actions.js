import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_IMPORT_DATA,
	SUCCESS_FETCH_IMPORT_DATA,
	ERROR_FETCH_IMPORT_DATA,
	START_START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
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
 * Fetch importer data action creator.
 */
export function* fetchImporterData() {
	yield startFetch();

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH,
		} );

		yield successFetch( normalizeImportData( data ) );
	} catch ( error ) {
		yield errorFetch( error );
	}
}

/**
 * @typedef  {Object} SuccessImporterDataAction
 * @property {string} type                         Action type.
 * @property {Object} data                         Importer data.
 */
/**
 * Success fetch action creator.
 *
 * @param {Object} data Importer data.
 *
 * @return {SuccessImporterDataAction} Success fetch action.
 */
export const successFetch = ( data ) => ( {
	type: SUCCESS_FETCH_IMPORT_DATA,
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
	type: ERROR_FETCH_IMPORT_DATA,
	error,
} );

/**
 * @typedef  {Object} startFetchAction
 * @property {string} type Action type.
 */
/**
 * Start fetch importer data action creator.
 *
 * @return {startFetchAction} Start fetch action.
 */
export const startFetch = () => ( {
	type: START_FETCH_IMPORT_DATA,
} );

/**
 * Start import.
 *
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] On Success handler.
 * @param {Function} [options.onError]   On Error handler.
 */
export function* submitStartImport( { onSuccess, onError } = {} ) {
	yield startStartImport();

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH + 'start',
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
 * @typedef  {Object} startStartImportAction
 * @property {string} type Action type.
 */
/**
 * Start action to start import.
 *
 * @return {startStartImportAction} Start import action.
 */
export const startStartImport = () => ( {
	type: START_START_IMPORT,
} );

/**
 * @typedef  {Object} successStartImportAction
 * @property {string} type Action type.
 * @property {Object} data Data object.
 */
/**
 * Success submit action creator.
 *
 * @param {Object} data Importer data.
 * @return {successStartImportAction} Success submit action.
 */
export const successStartImport = ( data ) => ( {
	type: SUCCESS_START_IMPORT,
	data,
} );

/**
 * @typedef  {Object}         errorStartImportAction
 * @property {string}         type   Action type.
 * @property {Object|boolean} error  Error object or false.
 */
/**
 * Error start import job creator.
 *
 * @param {Object|boolean} error Error object or false.
 *
 * @return {errorStartImportAction} Error action.
 */
export const errorStartImport = ( error ) => ( {
	type: ERROR_START_IMPORT,
	error,
} );

/**
 * Upload a file for a level.
 *
 * @param {string}   level                 Level identifier.
 * @param {Object}   uploadData            Data to submit.
 * @param {Object}   [options]
 * @param {Function} [options.onSuccess] Step name.
 * @param {Function} [options.onError]   Data to submit.
 */
export function* uploadFileForLevel(
	level,
	uploadData,
	{ onSuccess, onError } = {}
) {
	yield startFileUploadAction( level, uploadData );

	try {
		const data = yield fetchFromAPI( {
			path: API_BASE_PATH + 'file/' + level,
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
export function* throwEarlyUploadError( level, errorMsg ) {
	yield errorFileUpload( level, {
		code: '',
		message: errorMsg,
	} );
}

/**
 * @typedef  {Object} startFileUploadAction
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
 * @return {startFileUploadAction} Start file upload action.
 */
export const startFileUploadAction = ( level, uploadData ) => ( {
	type: START_UPLOAD_IMPORT_DATA_FILE,
	level,
	uploadData,
} );

/**
 * @typedef  {Object}         successFileUploadAction
 * @property {string}         type              Action type.
 * @property {string}         level             Level identifier.
 * @property {Object|boolean} error             Error object or false.
 */
/**
 * Success upload file action.
 *
 * @param {string} level Level identifier.
 * @param {Object} data  Importer data.
 * @return {successFileUploadAction} Success file upload action.
 */
export const successFileUpload = ( level, data ) => ( {
	type: SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	level,
	data,
} );

/**
 * @typedef  {Object}         errorFileUploadAction
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
 * @return {errorFileUploadAction} Error action.
 */
export const errorFileUpload = ( level, error ) => ( {
	type: ERROR_UPLOAD_IMPORT_DATA_FILE,
	level,
	error,
} );
