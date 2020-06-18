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

	return {
		...levels,
	};
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
	if ( data.status === 'complete' ) {
		return [ 'upload', 'progress' ];
	}

	return [];
};

/**
 * Normalize importer data.
 *
 * @param {Object} data Importer data.
 *
 * @return {Object} Normalized importer data.
 */
export const normalizeImportData = ( { files, status, ...data } ) => ( {
	...data,
	import: status,
	upload: normalizeUploadsState( files ),
	completedSteps: parseCompletedSteps( status ),
} );
