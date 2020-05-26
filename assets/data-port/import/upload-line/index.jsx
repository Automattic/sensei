/* global FormData */

import { FormFileUpload, Dashicon } from '@wordpress/components';
import { useReducer } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	startUploadAction,
	uploadFailureAction,
	uploadSuccessAction,
	uploadLineReducer,
} from './data';

const initialLines = [
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

const uploadFile = ( files, lineKey, dispatch ) => {
	if ( files.length < 1 ) {
		return;
	}

	const file = files[ 0 ];

	if ( file.name.split( '.' ).pop() !== 'csv' ) {
		dispatch(
			uploadFailureAction(
				lineKey,
				__( 'Only CSV files are supported.', 'sensei-lms' )
			)
		);

		return;
	}

	dispatch( startUploadAction( lineKey, file.name ) );

	const data = new FormData();
	data.append( 'file', file );

	apiFetch( {
		path: `/sensei-internal/v1/import/file/${ lineKey }`,
		method: 'POST',
		body: data,
	} )
		.then( () => {
			dispatch( uploadSuccessAction( lineKey ) );
		} )
		.catch( ( error ) => {
			dispatch( uploadFailureAction( lineKey, error.message ) );
		} );
};

export const UploadLines = () => {
	const [ lines, dispatch ] = useReducer( uploadLineReducer, initialLines );

	const getLineMessage = ( line ) => {
		if ( line.hasError ) {
			return (
				<>
					<Dashicon
						className={ 'sensei-upload-file-line__icon error' }
						icon={ 'warning' }
					/>
					<p className={ 'sensei-upload-file-line__message error' }>
						{ line.errorMsg }
					</p>
				</>
			);
		} else if ( line.isUploaded ) {
			return (
				<p className={ 'sensei-upload-file-line__message' }>
					{ line.filename }
				</p>
			);
		}
	};

	return (
		<ol>
			{ lines.map( ( line ) => {
				const message = getLineMessage( line );

				return (
					<li
						key={ line.key }
						className={ 'sensei-upload-file-line' }
					>
						<p className={ 'sensei-upload-file-line__description' }>
							{ line.description }
						</p>
						<FormFileUpload
							accept={ '.csv' }
							onChange={ ( event ) =>
								uploadFile(
									event.target.files,
									line.key,
									dispatch
								)
							}
						>
							{ line.inProgress
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
