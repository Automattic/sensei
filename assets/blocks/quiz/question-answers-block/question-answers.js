/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { AnswerFeedbackToggle } from '../answer-feedback-block/answer-feedback-toggle';
import { QuestionContext } from '../question-block/question-context';

/**
 * Question Description control.
 *
 */
const QuestionAnswers = () => {
	const {
		answer,
		setAttributes,
		AnswerBlock,
		hasSelected,
		canHaveFeedback,
	} = useContext( QuestionContext );
	return (
		<div className={ classnames( 'sensei-lms-question-answers-block' ) }>
			{ AnswerBlock?.edit && (
				<>
					<AnswerBlock.edit
						attributes={ answer }
						setAttributes={ ( next ) =>
							setAttributes( {
								answer: { ...answer, ...next },
							} )
						}
						hasSelected={ hasSelected }
					/>
					{ canHaveFeedback && hasSelected && (
						<AnswerFeedbackToggle />
					) }
				</>
			) }
		</div>
	);
};

export default QuestionAnswers;
