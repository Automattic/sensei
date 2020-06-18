import {
	START_FETCH_IMPORT_DATA,
	SUCCESS_FETCH_IMPORT_DATA,
	ERROR_FETCH_IMPORT_DATA,
	START_START_IMPORT,
	SUCCESS_START_IMPORT,
	ERROR_START_IMPORT,
	ERROR_UPLOAD_IMPORT_DATA_FILE,
	START_UPLOAD_IMPORT_DATA_FILE,
	SUCCESS_UPLOAD_IMPORT_DATA_FILE,
} from './constants';

import { merge } from 'lodash';

const DEFAULT_STATE = {
	isFetching: true,
	fetchError: false,
	completedSteps: [],
	upload: {
		isSubmitting: false,
		errorMsg: null,
		courses: {
			isUploaded: false,
			inProgress: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
		lessons: {
			isUploaded: false,
			inProgress: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
		questions: {
			isUploaded: false,
			inProgress: false,
			hasError: false,
			errorMsg: null,
			filename: null,
		},
	},
	progress: {
		status: '',
		percentage: 0,
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
		case START_FETCH_IMPORT_DATA:
			return {
				...state,
				isFetching: true,
				fetchError: false,
			};

		case SUCCESS_FETCH_IMPORT_DATA:
			return {
				...merge( state, action.data ),
				isFetching: false,
			};

		case ERROR_FETCH_IMPORT_DATA:
			// No need to start a new job until we have our first active upload.
			const isErrorNoActiveJob =
				action.error.code === 'sensei_data_port_no_active_job';

			return {
				...state,
				isFetching: false,
				fetchError: isErrorNoActiveJob ? false : action.error,
			};

		case START_START_IMPORT:
			return {
				...state,
				upload: {
					...state.upload,
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
				isUploaded: false,
				inProgress: true,
				hasError: false,
				errorMsg: null,
				filename: null,
			} );

		case SUCCESS_UPLOAD_IMPORT_DATA_FILE:
			action.data.upload[ action.level ] = {
				...action.data.upload[ action.level ],
				inProgress: false,
				hasError: false,
				errorMsg: null,
			};

			return updateLevelState(
				state,
				action.level,
				action.data.upload[ action.level ]
			);

		case ERROR_UPLOAD_IMPORT_DATA_FILE:
			return updateLevelState( state, action.level, {
				isUploaded: false,
				inProgress: false,
				hasError: true,
				errorMsg: action.error.message,
				filename: null,
			} );

		default:
			return state;
	}
};
