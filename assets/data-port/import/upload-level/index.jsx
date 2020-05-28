/* global FormData */

import { FormFileUpload, Dashicon } from '@wordpress/components';
import { useReducer, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	startUploadAction,
	uploadFailureAction,
	uploadSuccessAction,
	uploadLevelReducer,
} from './data';

const initialLevels = [
	{
		key: 'courses',
		description: __( 'Courses CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
	{
		key: 'lessons',
		description: __( 'Lessons CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
	{
		key: 'questions',
		description: __( 'Questions CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
];

/**
 * Helper method to upload a file.
 *
 * @param {FileList} files     The files of the input.
 * @param {string}   levelKey  The level key.
 * @param {Function} dispatch  Dispatch function to update state on success/failure.
 */
const uploadFile = ( files, levelKey, dispatch ) => {
	if ( files.length < 1 ) {
		return;
	}

	const file = files[ 0 ];

	if ( file.name.split( '.' ).pop() !== 'csv' ) {
		dispatch(
			uploadFailureAction(
				levelKey,
				__( 'Only CSV files are supported.', 'sensei-lms' )
			)
		);

		return;
	}

	dispatch( startUploadAction( levelKey, file.name ) );

	const data = new FormData();
	data.append( 'file', file );

	apiFetch( {
		path: `/sensei-internal/v1/import/file/${ levelKey }`,
		method: 'POST',
		body: data,
	} )
		.then( () => {
			dispatch( uploadSuccessAction( levelKey ) );
		} )
		.catch( ( error ) => {
			dispatch( uploadFailureAction( levelKey, error.message ) );
		} );
};

/**
 * Helper method which calculates if the files are ready to be imported.
 *
 * @param {Array}    levels  The array of the Levels.
 * @return {boolean}         True if the files are ready.
 */
const isReady = ( levels ) => {
	let hasUploaded = false;

	for ( const level of levels ) {
		if ( level.inProgress ) {
			return false;
		}

		hasUploaded = hasUploaded || level.isUploaded;
	}

	return hasUploaded;
};

/**
 * A component which displays a list of upload levels. Each level has each own description, upload button and a
 * placeholder for messages.
 *
 * @param {Function} setReadyStatus  A callback which sets the state true if the levels are ready to be uploaded.
 */
export const UploadLevels = ( { setReadyStatus } ) => {
	const [ levels, dispatch ] = useReducer(
		uploadLevelReducer,
		initialLevels
	);

	useEffect( () => setReadyStatus( isReady( levels ) ), [
		levels,
		setReadyStatus,
	] );

	const getLevelMessage = ( level ) => {
		if ( level.hasError ) {
			return (
				<>
					<Dashicon
						className={ 'sensei-upload-file-line__icon error' }
						icon={ 'warning' }
					/>
					<p className={ 'sensei-upload-file-line__message error' }>
						{ level.errorMsg }
					</p>
				</>
			);
		} else if ( level.isUploaded ) {
			return (
				<p className={ 'sensei-upload-file-line__message' }>
					{ level.filename }
				</p>
			);
		}
	};

	return (
		<ol>
			{ levels.map( ( level ) => {
				const message = getLevelMessage( level );

				return (
					<li
						key={ level.key }
						className={ 'sensei-upload-file-line' }
					>
						<p className={ 'sensei-upload-file-line__description' }>
							{ level.description }
						</p>
						<FormFileUpload
							accept={ [ '.csv', '.txt' ] }
							onChange={ ( event ) =>
								uploadFile(
									event.target.files,
									level.key,
									dispatch
								)
							}
						>
							{ level.inProgress
								? __( 'Uploading…', 'sensei-lms' )
								: __( 'Upload', 'sensei-lms' ) }
						</FormFileUpload>
						{ message }
					</li>
				);
			} ) }
		</ol>
	);
};
