/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import scheduleControls from '../../../shared/data/timeout-controls';
import { createReducerFromActionMap } from '../../../shared/data/store-helpers';

import * as actions from './actions';

const EMPTY_STATE = {};

const mapJobState = ( job ) => {
	return job && ! job.deleted
		? {
				job: {
					...job,
					...job.status,
					files: job.files && Object.values( job.files ),
				},
		  }
		: EMPTY_STATE;
};
/**
 * Export store reducers.
 */
const reducers = {
	UPDATE_JOB: ( { job }, state ) =>
		state.job ? mapJobState( job ) : state,
	SET_JOB: ( { job } ) => mapJobState( job ),
	SET_ERROR: ( { error }, state ) => ( { ...state, error } ),
	CLEAR_JOB: () => EMPTY_STATE,
	DEFAULT: ( action, state ) => state,
};

/**
 * Export store resolvers.
 */
const resolvers = {
	/**
	 * Check for active job on first access.
	 */
	getJob: () => actions.checkForActiveJob(),
};

/**
 * Export store selectors
 */
const selectors = {
	getJobId: ( { job } ) => ( job && job.id ) || null,
	getJob: ( { job } ) => job,
	getError: ( { error } ) => error,
};

export const EXPORT_STORE = 'sensei/export';

const registerExportStore = () =>
	registerStore( EXPORT_STORE, {
		reducer: createReducerFromActionMap( reducers, EMPTY_STATE ),
		actions,
		selectors,
		resolvers,
		controls: { ...dataControls, ...scheduleControls },
	} );

export default registerExportStore;
