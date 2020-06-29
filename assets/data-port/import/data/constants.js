/**
 * Data import constants.
 */
export const API_BASE_PATH = '/sensei-internal/v1/import/';
export const API_SPECIAL_ACTIVE_JOB_ID = 'active';

/**
 * Generic fetch action type constants.
 */
export const FETCH_FROM_API = 'FETCH_FROM_API';

/**
 * Fetch action type constants.
 */
export const START_FETCH_CURRENT_JOB_STATE = 'START_FETCH_CURRENT_JOB_STATE';
export const SUCCESS_FETCH_CURRENT_JOB_STATE =
	'SUCCESS_FETCH_CURRENT_JOB_STATE';
export const ERROR_FETCH_CURRENT_JOB_STATE = 'ERROR_FETCH_CURRENT_JOB_STATE';

/**
 * Import step action type constants.
 */
export const SET_STEP_DATA = 'SET_STEP_DATA';

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
 * Reset to default state.
 */
export const RESET_STATE = 'RESET_STATE';
