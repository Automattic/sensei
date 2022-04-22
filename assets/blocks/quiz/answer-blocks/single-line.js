/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Question block single-line answer component.
 *
 * @param {Object} props
 * @param {Object} props.children
 */
const SingleLineAnswer = ( { children } ) => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__answer--single-line">
			<small className="sensei-lms-question-block__input-label">
				{ __( 'Answer:', 'sensei-lms' ) }
			</small>
			<div className="sensei-lms-question-block__text-input-placeholder">
				{ children }
			</div>
		</div>
	);
};

export default SingleLineAnswer;
