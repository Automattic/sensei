/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Question block file upload answer component.
 */
const FileUploadAnswer = () => {
	return (
		<div className="wp-block-button is-style-outline sensei-lms-question-block__answer sensei-lms-question-block__answer--file-upload">
			<div className="wp-block-button__link wp-element-button">
				{ __( 'Choose File', 'sensei-lms' ) }
			</div>
		</div>
	);
};

export default FileUploadAnswer;
