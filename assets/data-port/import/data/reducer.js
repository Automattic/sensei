/**
 * External dependencies
 */
import { merge } from 'lodash';

/**
 * Internal dependencies
 */
import {
	START_LOAD_CURRENT_JOB_STATE,
	SUCCESS_LOAD_CURRENT_JOB_STATE,
	ERROR_LOAD_CURRENT_JOB_STATE,
	START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
	START_DELETE_IMPORT_DATA_FILE,
	SUCCESS_DELETE_IMPORT_DATA_FILE,
	ERROR_DELETE_IMPORT_DATA_FILE,
	RESET_STATE,
	SET_JOB_STATE,
	SET_IMPORT_LOG,
	ERROR_FETCH_IMPORT_LOG,
} from './constants';

const DEFAULT_STATE = {
	jobId: null,
	isFetching: true,
	fetchError: false,
	completedSteps: [],
	upload: {
		isSubmitting: false,
		errorMsg: null,
		courses: {
			isUploaded: false,
			isUploading: false,
			isDeleting: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
		lessons: {
			isUploaded: false,
			isUploading: false,
			isDeleting: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
		questions: {
			isUploaded: false,
			isUploading: false,
			isDeleting: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
	},
	progress: {
		status: '',
		percentage: 0,
	},
	done: {
		results: null,
		logs: null,
	},
};

/**
 *
 * @param {Object}         state      Current state.
 * @param {{type: string}} levelKey   Level to update.
 * @param {Object}         attributes Attributes to set.
 * @return {Object} State updated.
 */
const updateLevelState = ( state, levelKey, attributes ) => ( {
	...state,
	upload: {
		...state.upload,
		[ levelKey ]: attributes,
	},
} );

/**
 * Data importer reducer.
 *
 * @param {Object}         state  Current state.
 * @param {{type: string}} action Action to update the state.
 *
 * @return {Object} State updated.
 */
export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case START_LOAD_CURRENT_JOB_STATE:
			return {
				...state,
				isFetching: true,
				fetchError: false,
			};

		case SUCCESS_LOAD_CURRENT_JOB_STATE:
			return {
				...merge( {}, state, action.data ),
				isFetching: false,
			};

		case SET_JOB_STATE:
			return {
				...merge( {}, state, action.data ),
			};

		case ERROR_LOAD_CURRENT_JOB_STATE:
			return {
				...state,
				isFetching: false,
				fetchError: action.error,
			};

		case START_IMPORT:
			return {
				...state,
				upload: {
					...state.upload,
					errorMsg: null,
					isSubmitting: true,
				},
			};

		case ERROR_START_IMPORT:
			return {
				...state,
				upload: {
					...state.upload,
					errorMsg: action.error.message,
					isSubmitting: false,
				},
			};

		case SUCCESS_START_IMPORT:
			return {
				...state,
				completedSteps: action.data.completedSteps,
				upload: {
					...state.upload,
					isSubmitting: false,
				},
				progress: {
					...state.progress,
					...action.data.progress,
				},
			};

		case START_UPLOAD_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				...state.upload[ action.level ],
				isUploaded: false,
				isUploading: true,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			} );

		case SUCCESS_UPLOAD_IMPORT_DATA_FILE:
			return updateLevelState(
				{
					...state,
					jobId: action.data.jobId,
				},
				action.level,
				{
					...state.upload[ action.level ],
					...action.data.upload[ action.level ],
					isUploading: false,
					isDeleting: false,
					hasError: false,
					errorMsg: null,
				}
			);

		case ERROR_UPLOAD_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				...state.upload[ action.level ],
				isUploaded: false,
				isUploading: false,
				isDeleting: false,
				hasError: true,
				errorMsg: action.error.message,
				filename: null,
			} );

		case START_DELETE_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				...state.upload[ action.level ],
				isDeleting: true,
			} );

		case SUCCESS_DELETE_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				...action.data.upload[ action.level ],
				isUploaded: false,
				isDeleting: false,
				hasError: false,
				errorMsg: null,
				filename: null,
			} );

		case ERROR_DELETE_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				...state.upload[ action.level ],
				isUploaded: false,
				isDeleting: false,
				hasError: true,
				errorMsg: action.error.message,
			} );

		case SET_IMPORT_LOG:
			return {
				...state,
				done: {
					...state.done,
					logs: action.data,
				},
			};

		case ERROR_FETCH_IMPORT_LOG:
			return {
				...state,
				done: {
					...state.done,
					logs: { fetchError: action.error },
				},
			};

		case RESET_STATE:
			return { ...DEFAULT_STATE };

		default:
			return state;
	}
};
