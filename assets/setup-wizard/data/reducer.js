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
	SET_STEP_DATA,
} from './constants';

import { EXTERNAL_STATUS, INSTALLING_STATUS } from '../features/feature-status';
import { getWccomProductId } from '../../shared/helpers/woocommerce-com';

const DEFAULT_STATE = {
	isFetching: true,
	fetchError: false,
	isSubmitting: false,
	submitError: false,
	data: {
		completedSteps: [],
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
		ready: {},
	},
};

/**
 * @typedef  {Object} Feature
 * @property {string} slug   Feature slug.
 * @property {string} status Feature status.
 * @property {Object} error  Feature error.
 */
/**
 * Update status pre-installation.
 *
 * @param {string[]}  selected Feature slugs.
 * @param {Feature[]} options  Options.
 *
 * @return {Feature[]} Updated options.
 */
const updatePreInstallation = ( selected, options ) =>
	options.map( ( feature ) => {
		if ( selected.includes( feature.slug ) ) {
			return {
				...feature,
				status: getWccomProductId( feature )
					? EXTERNAL_STATUS
					: INSTALLING_STATUS,
				error: null,
			};
		}
		return feature;
	} );

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
			const { stepData, step } = action;
			let newState = null;

			// Clear status and error for retry.
			if ( 'features-installation' === step ) {
				newState = {
					...state,
					data: {
						...state.data,
						features: {
							...state.data.features,
							options: updatePreInstallation(
								stepData.selected,
								state.data.features.options
							),
						},
					},
				};
			}

			return {
				...( newState || state ),
				isSubmitting: true,
				submitError: false,
			};

		case SUCCESS_SUBMIT_SETUP_WIZARD_DATA:
			return {
				...state,
				isSubmitting: false,
				data: {
					...state.data,
					completedSteps: [
						...state.data.completedSteps,
						action.step,
					],
				},
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
