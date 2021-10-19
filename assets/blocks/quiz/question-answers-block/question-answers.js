/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';
/**
 * External dependencies
 */
import cn from 'classnames';
/**
 * Internal dependencies
 */
import { QuestionContext } from '../question-block/question-context';

/**
 * Question Description control.
 *
 */
const QuestionAnswers = () => {
	const { answer, setAttributes, AnswerBlock, hasSelected } = useContext(
		QuestionContext
	);
	return (
		<div className={ cn( 'sensei-lms-question-answers-block' ) }>
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
				</>
			) }
		</div>
	);
};

export default QuestionAnswers;
