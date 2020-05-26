const applyToLine = ( lineState, lineKey, modification ) =>
	lineState.map( ( line ) => {
		if ( line.key === lineKey ) {
			return modification( line );
		}

		return line;
	} );

export const startUploadAction = ( key, filename ) => ( {
	type: 'START_UPLOAD',
	payload: {
		key,
		filename,
	},
} );

export const uploadSuccessAction = ( key ) => ( {
	type: 'UPLOAD_SUCCESS',
	payload: {
		key,
	},
} );

export const uploadFailureAction = ( key, errorMsg ) => ( {
	type: 'UPLOAD_FAILURE',
	payload: {
		key,
		errorMsg,
	},
} );

export const uploadLineReducer = ( state, action ) => {
	switch ( action.type ) {
		case 'START_UPLOAD':
			return applyToLine( state, action.payload.key, ( line ) => {
				return {
					...line,
					inProgress: true,
					hasError: false,
					isUploaded: false,
					filename: action.payload.filename,
					errorMsg: null,
				};
			} );
		case 'UPLOAD_SUCCESS':
			return applyToLine( state, action.payload.key, ( line ) => {
				return {
					...line,
					inProgress: false,
					isUploaded: true,
				};
			} );
		case 'UPLOAD_FAILURE':
			return applyToLine( state, action.payload.key, ( line ) => {
				return {
					...line,
					inProgress: false,
					hasError: true,
					errorMsg: action.payload.errorMsg,
				};
			} );
		default:
			throw new Error( `Unknown action ${ action.type }.` );
	}
};
