/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Question block single-line answer component.
 */
const SingleLineAnswer = () => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__answer--single-line">
			<small className="sensei-lms-question-block__input-label">
				{ __( 'Answer:', 'sensei-lms' ) }
			</small>
			<div className="sensei-lms-question-block__text-input-placeholder" />
		</div>
	);
};

export default SingleLineAnswer;
