/**
 * Setup wizard constants.
 */
export const API_BASE_PATH = '/sensei-internal/v1/setup-wizard/';

/**
 * Generic fetch action type constants.
 */
export const FETCH_FROM_API = 'FETCH_FROM_API';

/**
 * Fetch action type constants.
 */
export const START_FETCH_SETUP_WIZARD_DATA = 'START_FETCH_SETUP_WIZARD_DATA';
export const SUCCESS_FETCH_SETUP_WIZARD_DATA =
	'SUCCESS_FETCH_SETUP_WIZARD_DATA';
export const ERROR_FETCH_SETUP_WIZARD_DATA = 'ERROR_FETCH_SETUP_WIZARD_DATA';

/**
 * Submit action type constants.
 */
export const START_SUBMIT_SETUP_WIZARD_DATA = 'START_SUBMIT_SETUP_WIZARD_DATA';
export const SUCCESS_SUBMIT_SETUP_WIZARD_DATA =
	'SUCCESS_SUBMIT_SETUP_WIZARD_DATA';
export const ERROR_SUBMIT_SETUP_WIZARD_DATA = 'ERROR_SUBMIT_SETUP_WIZARD_DATA';

/**
 * Welcome step action type constants.
 */
export const SET_STEP_DATA = 'SET_STEP_DATA';

/**
 * Run any side-effect for state changes.
 */
export const APPLY_STEP_DATA = 'APPLY_STEP_DATA';
