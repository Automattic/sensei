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

/**
 * Generate the REST API arguments from block attributes.
 *
 * @param {Object} attributes Block attributes.
 *
 * @return {Object} REST API parameters.
 */
export const getApiArgsFromAttributes = ( attributes ) => {
	const commonArgs = {
		id: attributes?.id,
		title: attributes?.title,
		type: attributes.type,
		grade: attributes.options?.grade,
	};

	return {
		...commonArgs,
		...getTypeArgs( attributes ),
	};
};

/**
 * Helper method to get type specific REST arguments.
 *
 * @param {Object} attributes Block attributes.
 *
 * @return {Object} Type specific arguments.
 */
const getTypeArgs = ( attributes ) => {
	switch ( attributes.type ) {
		case 'multiple-choice':
			return {
				answer_feedback: attributes.options?.answerFeedback || null,
				random_order: attributes.options?.randomOrder,
				options: attributes.answer?.answers.map(
					( { title, isRight } ) => ( {
						label: title,
						correct: isRight,
					} )
				),
			};
		case 'boolean':
			return {
				answer: attributes.answer?.rightAnswer,
				answer_feedback: attributes.options?.answerFeedback || null,
			};
		case 'gap-fill':
			return {
				before: attributes.answer?.textBefore || '',
				gap: attributes.answer?.rightAnswers || [],
				after: attributes.answer?.textAfter || '',
			};
		case 'single-line':
			return {};
		case 'multi-line':
			return {};
		case 'file-upload':
			return {};
	}
};
