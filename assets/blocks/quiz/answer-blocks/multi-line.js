/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Question block multi-line answer component.
 */
const MultiLineAnswer = () => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__answer--multi-line">
			<small className="sensei-lms-question-block__input-label">
				{ __( 'Answer:', 'sensei-lms' ) }
			</small>
			<div className="sensei-lms-question-block__text-input-placeholder multi-line" />
		</div>
	);
};

export default MultiLineAnswer;
