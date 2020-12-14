/**
 * Data import constants.
 */
export const API_BASE_PATH = '/sensei-internal/v1/import/';
export const API_SPECIAL_ACTIVE_JOB_ID = 'active';

/**
 * Generic fetch action type constants.
 */
export const FETCH_FROM_API = 'FETCH_FROM_API';
export const WAIT = 'WAIT';

/**
 * Load action type constants.
 */
export const START_LOAD_CURRENT_JOB_STATE = 'START_LOAD_CURRENT_JOB_STATE';
export const SUCCESS_LOAD_CURRENT_JOB_STATE = 'SUCCESS_LOAD_CURRENT_JOB_STATE';
export const ERROR_LOAD_CURRENT_JOB_STATE = 'ERROR_LOAD_CURRENT_JOB_STATE';
export const SET_JOB_STATE = 'SET_JOB_STATE';

/**
 * Start import job action type constants.
 */
export const START_IMPORT = 'START_IMPORT';
export const SUCCESS_START_IMPORT = 'SUCCESS_START_IMPORT';
export const ERROR_START_IMPORT = 'ERROR_START_IMPORT';

/**
 * Upload file constants.
 */
export const START_UPLOAD_IMPORT_DATA_FILE = 'START_UPLOAD_IMPORT_DATA_FILE';
export const SUCCESS_UPLOAD_IMPORT_DATA_FILE =
	'SUCCESS_UPLOAD_IMPORT_DATA_FILE';
export const ERROR_UPLOAD_IMPORT_DATA_FILE = 'ERROR_UPLOAD_IMPORT_DATA_FILE';

/**
 * Delete level file constants.
 */
export const START_DELETE_IMPORT_DATA_FILE = 'START_DELETE_IMPORT_DATA_FILE';
export const SUCCESS_DELETE_IMPORT_DATA_FILE =
	'SUCCESS_DELETE_IMPORT_DATA_FILE';
export const ERROR_DELETE_IMPORT_DATA_FILE = 'ERROR_DELETE_IMPORT_DATA_FILE';

/**
 * Import log constants.
 */
export const SET_IMPORT_LOG = 'SET_IMPORT_LOG';
export const ERROR_FETCH_IMPORT_LOG = 'ERROR_FETCH_IMPORT_LOG';

/**
 * Reset to default state.
 */
export const RESET_STATE = 'RESET_STATE';
