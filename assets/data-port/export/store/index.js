import { registerStore } from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';
import scheduleControls from './schedule';
import { createSimpleReducer } from '../../../shared/data/store-helpers';

import * as actions from './actions';

/**
 * Export store reducers.
 */
const reducers = {
	SET_JOB: ( { job } ) => ( { job } ),
	SET_ERROR: ( { error }, state ) => ( { ...state, error } ),
	CLEAR_JOB: () => ( {} ),
	DEFAULT: ( action, state ) => state,
};

/**
 * Export store selectors
 */
const selectors = {
	getJobId: ( { job } ) => ( job && job.id ) || null,
	getJob: ( { job, error } ) =>
		job
			? {
					error,
					...job,
					...job.status,
			  }
			: null,
};

export const EXPORT_STORE = 'sensei/export';

const registerExportStore = () => {
	registerStore( EXPORT_STORE, {
		reducer: createSimpleReducer( reducers, {} ),
		actions,
		selectors,
		controls: { ...dataControls, ...scheduleControls },
	} );
};

export default registerExportStore;
