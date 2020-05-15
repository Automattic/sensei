import {
	START_FETCH_SETUP_WIZARD_DATA,
	SET_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_WELCOME_STEP_DATA,
} from './constants';

const DEFAULT_STATE = {
	isFetching: false,
	isSubmitting: false,
	error: false,
	data: {
		welcome: {
			usage_tracking: false,
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
		case START_FETCH_SETUP_WIZARD_DATA:
			return {
				...state,
				isFetching: true,
			};

		case SET_SETUP_WIZARD_DATA:
			return {
				...state,
				isFetching: false,
				isSubmitting: false,
				error: false,
				data: {
					...state.data,
					...action.data,
				},
			};

		case START_SUBMIT_SETUP_WIZARD_DATA:
			return {
				...state,
				isSubmitting: true,
			};

		case SUCCESS_SUBMIT_SETUP_WIZARD_DATA:
			return {
				...state,
				isSubmitting: false,
			};

		case ERROR_SUBMIT_SETUP_WIZARD_DATA:
			return {
				...state,
				isSubmitting: false,
				error: action.error,
			};

		case SET_WELCOME_STEP_DATA:
			return {
				...state,
				data: {
					...state.data,
					welcome: {
						...state.data.welcome,
						...action.data,
					},
				},
			};

		default:
			return state;
	}
};
