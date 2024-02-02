/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { AnswerFeedbackToggle } from '../answer-feedback-block/answer-feedback-toggle';
import { QuestionContext } from '../question-block/question-context';
import { useBlockProps } from '@wordpress/block-editor';

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

	const blockProps = useBlockProps( {
		className: 'sensei-lms-question-answers-block',
	} );

	return (
		<div { ...blockProps }>
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
