import {
	FETCH_USAGE_TRACKING,
	SUBMIT_USAGE_TRACKING,
	ERROR_USAGE_TRACKING,
	SET_USAGE_TRACKING,
} from './constants';

const DEFAULT_STATE = {
	welcome: {
		isFetching: false,
		isSubmitting: false,
		error: false,
		data: {
			usageTracking: false,
		},
	},
};

/**
 * Setup wizard reducer.
 *
 * @param {Object}         state  Current state.
 * @param {{type: string}} action Action to update the state.
 *
 * @return {Object} State updated.
 */
export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case FETCH_USAGE_TRACKING:
			return {
				...state,
				welcome: {
					...state.welcome,
					isFetching: true,
				},
			};

		case SUBMIT_USAGE_TRACKING:
			return {
				...state,
				welcome: {
					...state.welcome,
					isSubmitting: true,
				},
			};

		case ERROR_USAGE_TRACKING:
			return {
				...state,
				welcome: {
					...state.welcome,
					isFetching: false,
					isSubmitting: false,
					error: action.error,
				},
			};

		case SET_USAGE_TRACKING:
			return {
				...state,
				welcome: {
					...state.welcome,
					isFetching: false,
					isSubmitting: false,
					error: false,
					data: {
						...state.welcome.data,
						usageTracking: action.usageTracking,
					},
				},
			};

		default:
			return state;
	}
};
