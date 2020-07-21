import apiFetch from '@wordpress/api-fetch';

/**
 * @typedef Job
 *
 * @property {Object}   status            Job status.
 * @property {string}   status.status     Job status name.
 * @property {number}   status.percentage Job progress percentage.
 * @property {string}   id                Job ID.
 * @property {boolean}  deleted           Was the job deleted.
 * @property {Object}   result            Job result.
 * @property {Object[]} files             Job files.
 */

/**
 * Client for the export API.
 *
 * @param {Function} updateState
 * @return {{cancel: Function, start: Function, reset: Function, update: Function}} Export store actions.
 */
export function ExportStore( updateState ) {
	/**
	 * API endpoint prefix.
	 *
	 * @type {string}
	 */
	const apiPrefix = '/sensei-internal/v1/export';

	/**
	 * Counter to track latest API request to discard old responses.
	 *
	 * @type {number}
	 */
	let lastApiRequestId = 0;

	/**
	 * Timer handle if polling is active, or false.
	 *
	 * @type {number|null}
	 */
	let polling = null;

	/**
	 * Current job ID.
	 *
	 * @type {string|null}
	 */
	let id = null;

	/**
	 * Start an export.
	 *
	 * @access public
	 * @param {string[]} types Content types.
	 */
	const start = async ( types ) => {
		updateState( {
			status: 'started',
			percentage: 0,
		} );

		await createJob();
		await startJob( types );
	};

	/**
	 * Reset state.
	 *
	 * @access public
	 */
	const reset = () => {
		updateState( null );
		clearPoll();
		id = null;
	};

	/**
	 * Request to delete the job.
	 *
	 * @access public
	 */
	const cancel = () => {
		updateState( null );
		clearPoll();
		return request( {
			path: `${ apiPrefix }/${ id }`,
			method: 'DELETE',
		} );
	};

	/**
	 * Update job state from REST API.
	 */
	const update = async () => {
		try {
			await request( {
				path: `${ apiPrefix }/${ id || 'active' }`,
			} );
		} catch ( err ) {
			setState( null );
		}
	};

	/**
	 * Perform REST API request and apply returned job state.
	 *
	 * @param {Object} options Request object.
	 * @return {Promise<Job|void>} Job state.
	 */
	const request = async ( options ) => {
		const requestId = ++lastApiRequestId;
		const job = await apiFetch( options );
		// Drop old responses.
		if ( lastApiRequestId > requestId ) {
			return;
		}
		setState( job );
		return job;
	};

	/**
	 * Request to create a new job.
	 */
	const createJob = () => {
		return request( {
			path: `${ apiPrefix }`,
			method: 'POST',
		} );
	};

	/**
	 * Request to start job.
	 *
	 * @param {string[]} types Content types to export.
	 */
	const startJob = ( types ) => {
		return request( {
			path: `${ apiPrefix }/${ id }/start`,
			method: 'POST',
			data: { content_types: types },
		} );
	};

	/**
	 * Set job state from response and call updateState callback.
	 * Starts polling for changes if the job status is not completed.
	 *
	 * @param {Job|null} job
	 */
	const setState = ( job ) => {
		if ( ! job || job.deleted ) {
			id = null;
			updateState( null );
			return;
		}
		id = job.id;
		updateState( { ...job.status } );

		if ( 'completed' !== job.status.status ) {
			poll();
		}
	};

	/**
	 * Schedule an update request.
	 */
	const poll = () => {
		polling = setTimeout( () => update(), 1000 );
	};

	/**
	 * Clear scheduled update request.
	 */
	const clearPoll = () => {
		if ( polling ) {
			clearTimeout( polling );
		}
	};

	updateState( null );
	update();

	return { start, cancel, reset, update };
}
