/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Has quiz hook.
 *
 * @return {boolean} If a quiz exists with questions.
 */
const useHasQuiz = () => {
	const [ hasQuiz, setHasQuiz ] = useState( () => {
		const questionCount = document.getElementById( 'question_counter' );

		return questionCount && parseInt( questionCount.value, 10 ) > 0;
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

	return hasQuiz;
};

export default useHasQuiz;
