/**
 * Helper method which applies a modification to a single upload level state.
 *
 * @param {Level[]}  levels        The state of all Levels.
 * @param {string}   levelKey      Unique key for the level.
 * @param {Function} modification  Modification to apply.
 * @return {Level[]} The new state.
 */
const applyToLevel = ( levels, levelKey, modification ) =>
	levels.map( ( level ) => {
		if ( level.key === levelKey ) {
			return modification( level );
		}

		return level;
	} );

/**
 * @typedef  {Object}  StartUploadAction
 * @property {string}  type               The type of the action
 * @property {Object}  payload            An object which contains two strings, the level key and the filename.
 */
/**
 * Returns a StartUploadAction.
 *
 * @param {string} key         The level key.
 * @param {string} filename    The filename of the upload
 * @return {StartUploadAction} The action.
 */
export const startUploadAction = ( key, filename ) => ( {
	type: 'START_UPLOAD',
	payload: {
		key,
		filename,
	},
} );

/**
 * @typedef  {Object}  UploadSuccessAction
 * @property {string}  type               The type of the action
 * @property {Object}  payload            An object which contains the level key.
 */
/**
 * Returns an UploadSuccessAction.
 *
 * @param {string} key           The level key.
 * @return {UploadSuccessAction} The action.
 */
export const uploadSuccessAction = ( key ) => ( {
	type: 'UPLOAD_SUCCESS',
	payload: {
		key,
	},
} );

/**
 * @typedef  {Object}  UploadFailureAction
 * @property {string}  type               The type of the action
 * @property {Object}  payload            An object which contains two strings, the level key and the error message.
 */
/**
 * Returns an UploadFailureAction.
 *
 * @param {string} key           The level key.
 * @param {string} errorMsg      The error message.
 * @return {UploadFailureAction} The action.
 */
export const uploadFailureAction = ( key, errorMsg ) => ( {
	type: 'UPLOAD_FAILURE',
	payload: {
		key,
		errorMsg,
	},
} );

/**
 * @typedef  {Object}  Level        A level for which data are imported.
 * @property {string}  key          Unique key for the level.
 * @property {string}  description  Description of the level.
 * @property {boolean} isUploaded   Whether the file for the level has been uploaded successfully.
 * @property {boolean} inProgress   Whether the upload for the level is in progress.
 * @property {boolean} hasError     Whether the was an error during the upload process.
 * @property {boolean} errorMsg     The error message.
 * @property {boolean} filename     The filename of the upload.
 */
/**
 * A reducer for level state.
 *
 * @param {Level[]}   state  The existing state.
 * @param {Object}    action The action.
 * @return {Level[]}         The new state.
 */
export const uploadLevelReducer = ( state, action ) => {
	switch ( action.type ) {
		case 'START_UPLOAD':
			return applyToLevel( state, action.payload.key, ( level ) => {
				return {
					...level,
					inProgress: true,
					hasError: false,
					isUploaded: false,
					filename: action.payload.filename,
					errorMsg: null,
				};
			} );
		case 'UPLOAD_SUCCESS':
			return applyToLevel( state, action.payload.key, ( level ) => {
				return {
					...level,
					inProgress: false,
					isUploaded: true,
				};
			} );
		case 'UPLOAD_FAILURE':
			return applyToLevel( state, action.payload.key, ( level ) => {
				return {
					...level,
					inProgress: false,
					hasError: true,
					errorMsg: action.payload.errorMsg,
				};
			} );
		default:
			throw new Error( `Unknown action ${ action.type }.` );
	}
};
