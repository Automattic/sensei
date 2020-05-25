import { FormFileUpload } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const lines = [
	{
		key: 'courses',
		description: __( 'Courses CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
	},
	{
		key: 'lessons',
		description: __( 'Lessons CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
	},
	{
		key: 'questions',
		description: __( 'Questions CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
	},
];

export const UploadLines = () => (
	<ol>
		{ lines.map( ( line ) => {
			return (
				<li key={ line.key } className={ 'sensei-upload-file-line' }>
					<p className={ 'sensei-upload-file-line__description' }>{line.description }</p>
					<FormFileUpload accept={ '.csv' }>{ __( 'Upload', 'sensei-lms' ) }</FormFileUpload>
				</li>
			);
		} ) }
	</ol>
);
