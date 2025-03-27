/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Has quiz hook.
 *
 * @return {boolean} If a quiz exists with questions.
 */
const useHasQuiz = () => {
	const [ hasQuiz, setHasQuiz ] = useState( () => {
		const questionCount = document.getElementById( 'question_counter' );

		return questionCount ? parseInt( questionCount.value, 10 ) > 0 : null;
	} );

	useEffect( () => {
		const quizToggleEventHandler = ( event ) => {
			setHasQuiz( event.detail.questions > 0 );
		};

		window.addEventListener(
			'sensei-quiz-editor-question-count-updated',
			quizToggleEventHandler
		);

		return () => {
			window.removeEventListener(
				'sensei-quiz-editor-question-count-updated',
				quizToggleEventHandler
			);
		};
	}, [] );

	const { _quiz_has_questions: quizHasQuestionsMeta } =
		useSelect( ( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		) || {};

	return null !== hasQuiz ? hasQuiz : quizHasQuestionsMeta;
};

export default useHasQuiz;
