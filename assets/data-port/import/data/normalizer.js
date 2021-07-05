/**
 * Normalize uploads state.
 *
 * @param {Object} files Files raw data.
 *
 * @return {Object} Normalized levels data.
 */
export const normalizeUploadsState = ( files ) => {
	const levels = {};

	Object.entries( files ).forEach( ( [ level, file ] ) => {
		if ( file.name ) {
			levels[ level ] = {
				filename: file.name,
				isUploaded: true,
			};
		}
	} );

	return levels;
};

/**
 * Parses completed steps data.
 *
 * @param {Object} data Status data.
 *
 * @return {Array} Parsed completed steps data.
 */
export const parseCompletedSteps = ( data ) => {
	if ( data.status === 'pending' ) {
		return [ 'upload' ];
	}
	if ( data.status === 'completed' ) {
		return [ 'upload', 'progress' ];
	}

	return [];
};

/**
 * Normalize importer data.
 *
 * @param {Object} input         Importer data.
 * @param {number} input.id      The job id.
 * @param {Object} input.files   Files raw data.
 * @param {string} input.status  Job status.
 * @param {Object} input.results Results of the job.
 * @param {Object} input.data    Job data.
 *
 * @return {Object} Normalized importer data.
 */
export const normalizeImportData = ( {
	id,
	files,
	status,
	results,
	...data
} ) => ( {
	...data,
	jobId: id,
	progress: status,
	upload: normalizeUploadsState( files || [] ),
	completedSteps: parseCompletedSteps( status || {} ),
	done: {
		results,
	},
} );
