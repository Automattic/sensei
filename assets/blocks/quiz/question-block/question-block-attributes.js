/**
 * Map question data to block attributes.
 *
 * @param {Object} question Question data.
 */
export function createQuestionBlockAttributes( question ) {
	const {
		grade,
		type,
		title,
		id,
		categories,
		shared,
		answer_feedback: answerFeedback,
		teacher_notes: gradingNotes,
		...props
	} = question;
	const typeAttributes = getTypeAttributes[ type ]?.( props );
	return {
		id,
		title,
		type,
		shared,
		...typeAttributes,
		options: {
			grade,
			categories,
			answerFeedback,
			gradingNotes,
			...typeAttributes?.options,
		},
	};
}

/**
 * Type-specific block attributes.
 */
const getTypeAttributes = {
	'gap-fill': ( { before, after, gap } ) => ( {
		answer: {
			textBefore: before,
			textAfter: after,
			rightAnswers: gap,
		},
	} ),
	'multiple-choice': ( { options, random_order: randomOrder } ) => ( {
		answer: {
			answers: options.map( ( { label: title, correct: isRight } ) => ( {
				title,
				isRight,
			} ) ),
		},
		options: {
			randomOrder,
		},
	} ),
	boolean: ( { answer: rightAnswer } ) => ( {
		answer: {
			rightAnswer,
		},
	} ),
};
