/**
 * Check whether the selected multiple-choice answers match the correct answers.
 *
 * @param {string} userAnswer    Selected answers separated by `<br>`.
 * @param {string} correctAnswer Correct answers separated by `<br>`.
 * @return {boolean} Whether all answers are correct.
 */
export const areMultipleChoiceAnswersCorrect = (
	userAnswer,
	correctAnswer
) => {
	const userAnswers = userAnswer.split( '<br>' );
	const correctAnswers = correctAnswer
		.split( '<br>' )
		.filter( ( answer ) => answer !== '' );

	return (
		userAnswers.length === correctAnswers.length &&
		userAnswers.every( ( answer ) => correctAnswers.includes( answer ) )
	);
};
