/* global FormData */

import { useSelect, useDispatch } from '@wordpress/data';
import { FormFileUpload } from '@wordpress/components';
import { Notice } from '../../notice';
import { __ } from '@wordpress/i18n';

const levels = [
	{
		key: 'courses',
		description: __( 'Courses CSV File', 'sensei-lms' ),
	},
	{
		key: 'lessons',
		description: __( 'Lessons CSV File', 'sensei-lms' ),
	},
	{
		key: 'questions',
		description: __( 'Questions CSV File', 'sensei-lms' ),
	},
];

/**
 * Helper method to upload a file.
 *
 * @param {FileList} files                 The files of the input.
 * @param {string}   levelKey              The level key.
 * @param {Function} uploadFileForLevel       Callback for action to upload file.
 * @param {Function} throwEarlyUploadError Callback for throwing an early upload error.
 */
const uploadFile = (
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

	uploadFileForLevel( levelKey, data );
};

/**
 * A component which displays a list of upload levels. Each level has each own description, upload button and a
 * placeholder for messages.
 */
export const UploadLevels = () => {
	const { levelsState } = useSelect( ( select ) => {
		const store = select( 'sensei/import' );
		return {
			levelsState: store.getStepData( 'upload' ).levels,
		};
	}, [] );

	const { uploadFileForLevel, throwEarlyUploadError } = useDispatch(
		'sensei/import'
	);

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
				const levelState = levelsState[ level.key ];
				const message = getLevelMessage( levelState );

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
