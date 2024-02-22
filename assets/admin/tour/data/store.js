/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { createReducerFromActionMap } from '../../../shared/data/store-helpers';
import { controls } from '@wordpress/data-controls';

export const SENSEI_TOUR_STORE = 'sensei/tour';

const DEFAULT_STATE = {
	showTour: true,
};

/**
 * Tour store actions.
 */
const actions = {
	/**
	 * Sets whether the tour should be shown.
	 *
	 * @param {boolean} show The lesson status.
	 *
	 * @return {Object} The setTourShowStatus action.
	 */
	setTourShowStatus( show ) {
		return {
			type: 'SET_TOUR_SHOW_STATUS',
			showTour: show,
		};
	},
};

/**
 * Tour store selectors.
 */
const selectors = {
	/**
	 * Get if the tour should be shown.
	 *
	 * @param {Object}  state          The state.
	 * @param {boolean} state.showTour If the tour should be shown.
	 *
	 * @return {boolean} If the tour should be shown.
	 */
	getIfShowTour: ( { showTour } ) => showTour,
};

/**
 * Tour store reducer.
 */
const reducers = {
	/**
	 * Sets the show tour status.
	 *
	 * @param {Object}  action          The action.
	 * @param {boolean} action.showTour If the tour should be shown.
	 * @param {Object}  state           The state.
	 *
	 * @return {Object} The new state.
	 */
	SET_TOUR_SHOW_STATUS: ( { showTour }, state ) => {
		return {
			...state,
			showTour,
		};
	},
	DEFAULT: ( action, state ) => state,
};

const store = createReduxStore( SENSEI_TOUR_STORE, {
	reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
	actions,
	selectors,
	controls,
} );

register( store );
