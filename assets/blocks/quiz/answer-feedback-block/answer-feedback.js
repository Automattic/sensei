/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */
import { QuestionContext } from '../question-block/question-context';

const config = {
	correct: {
		title: __( 'Correct', 'sensei-lms' ),
		placeholder: __(
			'Show a message when the question is answered correctly. Type / to choose a block.',
			'sensei-lms'
		),
	},
	incorrect: {
		title: __( 'Incorrect', 'sensei-lms' ),
		placeholder: __(
			'Show a message when the question is answered incorrectly. Type / to choose a block.',
			'sensei-lms'
		),
	},
};

/**
 * Answer Feedback control.
 *
 * @param {Object} props
 * @param {string} props.type correct or incorrect
 */
const AnswerFeedback = ( { type } ) => {
	const { title, placeholder } = config[ type ];
	const questionContext = useContext( QuestionContext );
	const { hideAnswerFeedback } = questionContext.options;

	if ( hideAnswerFeedback ) {
		return '';
	}
	return (
		<div
			className={ classnames(
				'sensei-lms-question__answer-feedback',
				`sensei-lms-question__answer-feedback--${ type }`
			) }
		>
			<div className="sensei-lms-question__answer-feedback__header">
				<span
					className={ 'sensei-lms-question__answer-feedback__icon' }
				/>
				<span>{ title }</span>
			</div>
			<div className="sensei-lms-question__answer-feedback__content">
				<InnerBlocks
					template={ [
						[
							'core/paragraph',
							{
								placeholder,
							},
						],
					] }
					templateInsertUpdatesSelection={ false }
					templateLock={ false }
				/>
			</div>
		</div>
	);
};

export default AnswerFeedback;
