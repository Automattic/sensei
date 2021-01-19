import { useEffect, useState } from '@wordpress/element';

/**
 * Has quiz hook.
 *
 * @param {Object}   options            Hook options.
 * @param {Function} options.quizToggle Toggle the quiz block.
 *
 * @return {boolean} If a quiz exists with questions.
 */
export const useHasQuiz = ( { quizToggle } ) => {
	const [ quizEventListener ] = useState( null );
	const [ hasQuiz, setHasQuiz ] = useState( () => {
		const questionCount = document.getElementById( 'question_counter' );

		return questionCount && parseInt( questionCount.value, 10 ) > 0;
	} );

	useEffect( () => {
		quizToggle( hasQuiz );
	}, [ hasQuiz ] );

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
	}, [ quizEventListener ] );

	return hasQuiz;
};
