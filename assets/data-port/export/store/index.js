import { registerStore } from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';
import scheduleControls from '../../../shared/data/schedule-controls';
import { createReducerFromActionMap } from '../../../shared/data/store-helpers';

import * as actions from './actions';

/**
 * Export store reducers.
 */
const reducers = {
	SET_JOB: ( { job } ) => ( {
		job: {
			...job,
			...job.status,
		},
	} ),
	SET_ERROR: ( { error }, state ) => ( { ...state, error } ),
	CLEAR_JOB: () => ( {} ),
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
	getRequestError: ( { error } ) => error,
};

export const EXPORT_STORE = 'sensei/export';

const registerExportStore = () =>
	registerStore( EXPORT_STORE, {
		reducer: createReducerFromActionMap( reducers, {} ),
		actions,
		selectors,
		resolvers,
		controls: { ...dataControls, ...scheduleControls },
	} );

export default registerExportStore;
