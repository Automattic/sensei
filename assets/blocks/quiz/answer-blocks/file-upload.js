/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Question block file upload answer component.
 */
const FileUploadAnswer = () => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__answer--file-upload">
			<div className="sensei-lms-question-block__file-input-placeholder">
				{ __( 'Choose File', 'sensei-lms' ) }
			</div>
		</div>
	);
};

export default FileUploadAnswer;
