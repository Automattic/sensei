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
	isFetching: false,
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
		ready: {},
	},
};

/**
 * @typedef  {Object} Feature
 * @property {string} slug    Feature slug.
 * @property {string} status  Feature status.
 * @property {Object} error   Feature error.
 */
/**
 * Remove error and status from selected options.
 *
 * @param {string[]}  selected Feature slugs.
 * @param {Feature[]} options  Options.
 *
 * @return {Feature[]} Updated options.
 */
const removeErrorFromSelected = ( selected, options ) =>
	options.map( ( feature ) => {
		if ( selected.includes( feature.slug ) ) {
			// Remove status and error props from the object.
			const {
				status, // eslint-disable-line no-unused-vars
				error, // eslint-disable-line no-unused-vars
				...featureWithoutError
			} = feature;
			return featureWithoutError;
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
							options: removeErrorFromSelected(
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
