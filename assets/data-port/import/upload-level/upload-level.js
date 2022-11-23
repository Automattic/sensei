/* global FormData */

/**
 * WordPress dependencies
 */
import { Button, FormFileUpload, Spinner } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Notice } from '../../notice';
import { levels } from '../levels';

/**
 * Helper method to upload a file.
 *
 * @param {string}   jobId                 The job identifier.
 * @param {FileList} files                 The files of the input.
 * @param {string}   levelKey              The level key.
 * @param {Function} uploadFileForLevel    Callback for action to upload file.
 * @param {Function} throwEarlyUploadError Callback for throwing an early upload error.
 */
const uploadFile = (
	jobId,
	files,
	levelKey,
	uploadFileForLevel,
	throwEarlyUploadError
) => {
	if ( files.length < 1 ) {
		return;
	}

	const file = files[ 0 ];

	if ( ! [ 'csv', 'txt' ].includes( file.name.split( '.' ).pop() ) ) {
		throwEarlyUploadError(
			levelKey,
			__( 'Only CSV files are supported.', 'sensei-lms' )
		);

		return;
	}

	const data = new FormData();
	data.append( 'file', file );

	uploadFileForLevel( jobId, levelKey, data );
};

/**
 * A component which displays a list of upload levels. Each level has each own description, upload button and a
 * placeholder for messages.
 *
 * @param {Object}   input                       UploadLevels input.
 * @param {number}   input.jobId                 The job id.
 * @param {Object}   input.state                 The import state.
 * @param {Function} input.uploadFileForLevel    Callback for action to upload file.
 * @param {Function} input.throwEarlyUploadError Callback for throwing an early upload error.
 * @param {Function} input.deleteLevelFile       The import state.
 */
export const UploadLevels = ( {
	jobId,
	state,
	uploadFileForLevel,
	throwEarlyUploadError,
	deleteLevelFile,
} ) => {
	const getLevelMessage = ( levelState ) => {
		if ( levelState.hasError ) {
			return <Notice message={ levelState.errorMsg } isError />;
		} else if ( levelState.isUploaded ) {
			return <Notice message={ levelState.filename } />;
		}
	};

	return (
		<ol>
			{ levels.map( ( level ) => {
				const levelState = state[ level.key ];
				const message = getLevelMessage( levelState );

				let deleteButton;
				if ( levelState.isDeleting ) {
					deleteButton = (
						<div className="sensei-upload-file-line__delete-button-wrapper">
							<Spinner />
						</div>
					);
				} else if ( levelState.isUploaded ) {
					deleteButton = (
						<div className="sensei-upload-file-line__delete-button-wrapper">
							<Button
								icon={ closeSmall }
								label={ __( 'Delete File', 'sensei-lms' ) }
								onClick={ () =>
									deleteLevelFile( jobId, level.key )
								}
								disabled={ levelState.isDeleting }
							/>
						</div>
					);
				}

				/* eslint-disable jsx-a11y/label-has-for */
				return (
					<li
						key={ level.key }
						className="sensei-upload-file-line sensei-data-port-step__line"
					>
						<label
							className="sensei-upload-file-line__description"
							htmlFor={ `sensei-upload-file-line-${ level.key }` }
						>
							{ level.description }
						</label>
						<FormFileUpload
							// Include key to redraw after each upload attempt for onChange of the same file.
							key={ levelState.isUploading }
							isSecondary
							id={ `sensei-upload-file-line-${ level.key }` }
							accept={ [ '.csv', '.txt' ] }
							disabled={
								levelState.isUploading || levelState.isDeleting
							}
							onChange={ ( event ) =>
								uploadFile(
									jobId,
									event.target.files,
									level.key,
									uploadFileForLevel,
									throwEarlyUploadError
								)
							}
						>
							{ levelState.isUploading
								? __( 'Uploading…', 'sensei-lms' )
								: __( 'Upload', 'sensei-lms' ) }
						</FormFileUpload>
						{ ( message || deleteButton ) && (
							<div className="sensei-upload-file-line__info">
								{ message }
								{ deleteButton }
							</div>
						) }
					</li>
				);
				/* eslint-enable */
			} ) }
		</ol>
	);
};
