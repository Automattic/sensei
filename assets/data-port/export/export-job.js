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
 * @class ExportJob
 */
export class ExportJob {
	/**
	 * API endpoint prefix.
	 *
	 * @type {string}
	 */
	static apiPrefix = '/sensei-internal/v1/export';

	/**
	 * Counter to track latest API request to discard old responses.
	 *
	 * @type {number}
	 */
	apiHit = 0;

	/**
	 * Timer handle if polling is active, or false.
	 *
	 * @type {number|null}
	 */
	polling = null;

	/**
	 * Current job ID.
	 *
	 * @type {string|null}
	 */
	id = null;

	/**
	 * Constructor.
	 *
	 * @param {Function} updateState Callback for job state change.
	 */
	constructor( updateState ) {
		this.updateState = updateState;
	}

	/**
	 * Update job state from REST API.
	 */
	async update() {
		try {
			await this.request( {
				path: `${ ExportJob.apiPrefix }/${ this.id || 'active' }`,
			} );
		} catch ( err ) {
			this.setState( null );
		}
	}

	/**
	 * Perform REST API request and apply returned job state.
	 *
	 * @param {Object} request Request object.
	 * @return {Promise<Job|void>} Job state.
	 */
	async request( request ) {
		const apiHit = ++this.apiHit;
		const job = await apiFetch( request );
		// Drop old responses.
		if ( this.apiHit > apiHit ) {
			return;
		}
		this.setState( job );
		return job;
	}

	/**
	 * Request to create a new job.
	 */
	createJob() {
		return this.request( {
			path: `${ ExportJob.apiPrefix }`,
			method: 'POST',
		} );
	}

	/**
	 * Request to delete the job.
	 */
	cancel() {
		this.updateState( null );
		this.clearPoll();
		return this.request( {
			path: `${ ExportJob.apiPrefix }/${ this.id }`,
			method: 'DELETE',
		} );
	}

	/**
	 * Request to start job.
	 *
	 * @param {string[]} types Content types to export.
	 */
	startJob( types ) {
		return this.request( {
			path: `${ ExportJob.apiPrefix }/${ this.id }/start`,
			method: 'POST',
			data: { content_types: types },
		} );
	}

	/**
	 * Set job state from response and call updateState callback.
	 * Starts polling for changes if the job status is not completed.
	 *
	 * @param {Job|null} job
	 */
	setState( job ) {
		if ( ! job || job.deleted ) {
			this.id = null;
			this.updateState( null );
			return;
		}
		this.id = job.id;
		this.updateState( { ...job.status } );

		if ( 'completed' !== job.status.status ) {
			this.poll();
		}
	}

	/**
	 * Start an export.
	 *
	 * @access public
	 * @param {string[]} types Content types.
	 */
	async start( types ) {
		this.updateState( {
			status: 'started',
			percentage: 0,
		} );

		await this.createJob();
		await this.startJob( types );
	}

	/**
	 * Schedule an update request.
	 */
	poll() {
		this.polling = setTimeout( () => this.update(), 1000 );
	}

	/**
	 * Clear scheduled update request.
	 */
	clearPoll() {
		if ( this.polling ) {
			clearTimeout( this.polling );
		}
	}
}
