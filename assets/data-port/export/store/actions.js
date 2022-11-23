/**
 * WordPress dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { EXPORT_STORE } from './index';
import { cancelTimeout, timeout } from '../../../shared/data/timeout-controls';

const EXPORT_REST_API = '/sensei-internal/v1/export';

/**
 * @typedef LogItem
 *
 * @property {string} message Log message.
 */

/**
 * @typedef LogsResponse
 *
 * @property {LogItem[]} items Log items.
 */

/**
 * @typedef JobResponse
 *
 * @property {Object}   status            Job status.
 * @property {string}   status.status     Job status name.
 * @property {number}   status.percentage Job progress percentage.
 * @property {string}   id                Job ID.
 * @property {boolean}  deleted           Was the job deleted.
 * @property {Object}   result            Job result.
 * @property {Object[]} files             Job files.
 * @property {Object}   error             Error message.
 */

/**
 * Set job state. Clears request error.
 *
 * @param {JobResponse} job Job state.
 */
const setJob = ( job ) => ( { type: 'SET_JOB', job } );
const updateJob = ( job ) => ( { type: 'UPDATE_JOB', job } );

const getJobId = () => select( EXPORT_STORE, 'getJobId' );

/**
 * Set error.
 *
 * @param {string} error Error message.
 */
const setError = ( error ) => ( { type: 'SET_ERROR', error } );

/**
 * Clear job state.
 */
const clearJob = () => ( { type: 'CLEAR_JOB' } );

/**
 * Start polling update if job status is pending.
 *
 * @param {JobResponse} job
 */
const pollIfPending = function* ( job ) {
	if ( job && ! job.error && 'pending' === job.status.status ) {
		yield timeout( update, 1000 );
	}
};

/**
 * Start an export.
 *
 * @access public
 * @param {string[]} types Content types.
 */
export const start = function* ( types ) {
	yield setJob( {
		status: 'creating',
	} );
	yield createJob();
	const job = yield startJob( types );
	yield pollIfPending( job );
};

/**
 * Reset state.
 *
 * @access public
 */
export const reset = function* () {
	yield cancelTimeout();
	yield clearJob();
};

/**
 * Request to delete the job.
 *
 * @param {string?} jobId
 * @access public
 */
export const cancel = function* ( jobId ) {
	yield cancelTimeout();
	if ( ! jobId ) {
		jobId = yield getJobId();
	}
	yield clearJob();
	yield sendJobRequest( {
		method: 'DELETE',
		jobId,
	} );
};

/**
 * Update job state from REST API.
 */
export const update = function* () {
	let jobId = yield getJobId();
	if ( ! jobId ) {
		return undefined;
	}
	const job = yield sendJobRequest( {
		endpoint: 'process',
		method: 'POST',
		jobId,
	} );

	jobId = yield getJobId();
	if ( ! jobId ) {
		return undefined;
	}
	yield updateJob( job );
	yield pollIfPending( job );
	yield getLogsIfCompleted( job );
};

const getLogsIfCompleted = function* ( job ) {
	if ( job.status.status === 'completed' ) {
		const logs = yield sendJobRequest( {
			endpoint: 'logs',
			skipJobCheck: true,
		} );
		if ( logs.items.length > 0 ) {
			yield setError( logs.items.map( ( i ) => i.message ).join( ' ' ) );
		}
	}
};

/**
 * Check if there is an active job and load it.
 */
export const checkForActiveJob = function* () {
	const job = yield sendJobRequest( { jobId: 'active' } );

	if ( job && job.id ) {
		if ( 'setup' === job.status.status ) {
			yield cancel( job.id );
		} else {
			yield setJob( job );
			yield pollIfPending( job );
		}
	}
};

/**
 * Perform REST API request for a job and apply returned job state.
 *
 * @param {Object}  options          apiFetch request object.
 * @param {string?} options.endpoint Request sub-path in exporter API.
 * @param {string?} options.jobId    Override job ID
 * @return {JobResponse|LogsResponse} Job or logs response.
 */
const sendJobRequest = function* ( options = {} ) {
	let { jobId, ...requestOptions } = options;

	if ( ! jobId ) {
		jobId = yield getJobId();
		if ( ! jobId ) {
			yield setError( 'No job ID' );
			return undefined;
		}
	}

	return yield* sendRequest( { jobId, ...requestOptions } );
};

/**
 * Perform REST API request and apply returned job state.
 *
 * @param {Object}   options              Request object.
 * @param {string?}  options.endpoint     Request endpoint path in exporter API.
 * @param {string?}  options.jobId        Job ID.
 * @param {boolean?} options.skipJobCheck Flag if should skip job check (getting job logs).
 */
const sendRequest = function* ( options = {} ) {
	const { skipJobCheck, endpoint, jobId, ...requestOptions } = options;

	const path = [ EXPORT_REST_API, jobId, endpoint ]
		.filter( ( i ) => !! i )
		.join( '/' );

	try {
		const response = yield apiFetch( { path, ...requestOptions } );

		if (
			skipJobCheck ||
			! response ||
			! jobId ||
			jobId === response.id ||
			'active' === jobId
		) {
			return response;
		}
	} catch ( error ) {
		if (
			'active' === jobId &&
			'sensei_data_port_job_not_found' === error.code
		) {
			return yield clearJob();
		}
		yield setError( error.message );
	}
};

/**
 * Request to create a new job.
 */
const createJob = function* () {
	const job = yield sendRequest( {
		method: 'POST',
	} );

	yield setJob( job );
};

/**
 * Request to start job.
 *
 * @param {string[]} types Content types to export.
 */
const startJob = function* ( types ) {
	const job = yield sendJobRequest( {
		endpoint: 'start',
		method: 'POST',
		data: { content_types: types },
	} );

	// Log when users start an export.
	const type = types
		.map( ( typeSingular ) => typeSingular + 's' )
		.join( ',' );

	window.sensei_log_event( 'export_continue_click', { type } );

	yield updateJob( job );
	return job;
};
