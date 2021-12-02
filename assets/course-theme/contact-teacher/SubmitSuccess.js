/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SuccessIcon } from '../../icons';

export const SubmitSuccess = () => {
	return (
		<div className="sensei-course-theme-contact-teacher__success__container">
			<SuccessIcon />
			<h1 className="sensei-course-theme-contact-teacher__success__title">
				{ __( 'Your message has been sent', 'sensei-lms' ) }
			</h1>
		</div>
	);
};
