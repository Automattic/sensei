/* global FormData */

import { FormFileUpload } from '@wordpress/components';
import { Notice } from '../../notice';
import { __ } from '@wordpress/i18n';
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
 */
export const UploadLevels = ( {
	jobId,
	state,
	uploadFileForLevel,
	throwEarlyUploadError,
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

				return (
					<li key={ level.key } className="sensei-upload-file-line">
						<p className="sensei-upload-file-line__description">
							{ level.description }
						</p>
						<FormFileUpload
							// Include key to redraw after each upload attempt for onChange of the same file.
							key={ levelState.inProgress }
							accept={ [ '.csv', '.txt' ] }
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
							{ levelState.inProgress
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
