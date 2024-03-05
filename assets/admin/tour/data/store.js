/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { createReducerFromActionMap } from '../../../shared/data/store-helpers';
import { controls } from '@wordpress/data-controls';
import apiFetch from '@wordpress/api-fetch';

export const SENSEI_TOUR_STORE = 'sensei/tour';

export const DEFAULT_STATE = {
	showTour: true,
};

/**
 * Tour store actions.
 */
export const actions = {
	/**
	 * Sets whether the tour should be shown.
	 *
	 * @param {boolean} show            The lesson status.
	 * @param {boolean} persistOnServer If the action should be persisted.
	 * @param {string}  tourName        The unique name of the tour.
	 *
	 * @return {Object} The setTourShowStatus action.
	 */
	setTourShowStatus( show, persistOnServer, tourName ) {
		if ( persistOnServer ) {
			apiFetch( {
				path: 'sensei-internal/v1/tour',
				method: 'POST',
				data: { complete: ! show, tour_id: tourName },
			} );
		}
		return {
			type: 'SET_TOUR_SHOW_STATUS',
			showTour: show,
		};
	},
};

/**
 * Tour store selectors.
 */
export const selectors = {
	/**
	 * Get if the tour should be shown.
	 *
	 * @param {Object}  state          The state.
	 * @param {boolean} state.showTour If the tour should be shown.
	 *
	 * @return {boolean} If the tour should be shown.
	 */
	shouldShowTour: ( { showTour } ) => showTour,
};

/**
 * Tour store reducer.
 */
export const reducers = {
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

export const store = createReduxStore( SENSEI_TOUR_STORE, {
	reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
	actions,
	selectors,
	controls,
} );

register( store );
