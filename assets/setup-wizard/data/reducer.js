/**
 * External dependencies
 */
import { mergeWith } from 'lodash';

/**
 * Internal dependencies
 */
import {
	START_FETCH_SETUP_WIZARD_DATA,
	SUCCESS_FETCH_SETUP_WIZARD_DATA,
	ERROR_FETCH_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_DATA,
} from './constants';

const DEFAULT_STATE = {
	isFetching: true,
	fetchError: false,
	isSubmitting: false,
	submitError: false,
	data: {
		purpose: {
			selected: [],
			other: '',
		},
		theme: {
			install_sensei_theme: false,
		},
		tracking: {
			usage_tracking: false,
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

		case SET_DATA:
			// It avoids merging arrays, so it works properly when removing item from arrays.
			const mergeCustomizer = ( objValue, srcValue ) => {
				if ( Array.isArray( srcValue ) ) {
					return srcValue;
				}
			};

			return {
				...state,
				data: mergeWith( state.data, action.data, mergeCustomizer ),
			};

		default:
			return state;
	}
};
