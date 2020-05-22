import {
	START_FETCH_SETUP_WIZARD_DATA,
	SUCCESS_FETCH_SETUP_WIZARD_DATA,
	ERROR_FETCH_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_STEP_DATA,
} from './constants';

const DEFAULT_STATE = {
	isFetching: true,
	fetchError: false,
	isSubmitting: false,
	submitError: false,
	data: {
		welcome: {
			usage_tracking: false,
		},
		purpose: {
			selected: [],
			other: '',
		},
		features: {
			selected: [],
			options: [],
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
				fetchError: false,
			};

		case SUCCESS_FETCH_SETUP_WIZARD_DATA:
			return {
				...state,
				isFetching: false,
				data: {
					...state.data,
					...action.data,
				},
			};

		case ERROR_FETCH_SETUP_WIZARD_DATA:
			return {
				...state,
				isFetching: false,
				fetchError: action.error,
			};

		case START_SUBMIT_SETUP_WIZARD_DATA:
			return {
				...state,
				isSubmitting: true,
				submitError: false,
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
				submitError: action.error,
			};

		case SET_STEP_DATA:
			return {
				...state,
				data: {
					...state.data,
					[ action.step ]: {
						...state.data[ action.step ],
						...action.data,
					},
				},
			};

		default:
			return state;
	}
};
