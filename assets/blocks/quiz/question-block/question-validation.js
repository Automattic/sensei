/**
 * WordPress dependencies
 */
/**
 * Internal dependencies
 */
import blockSettings from '.';

/**
 * Internal dependencies
 */
import types from '../answer-blocks';

/**
 * Validate question block
 *
 * @param {Object} attributes Block attributes.
 * @return {Array} Validation errors.
 */
export const validateQuestionBlock = ( attributes ) => {
	const { id, type, title, answer } = attributes;
	const answerBlock = type && types[ type ];
	const answerBlockValidation = answerBlock.validate?.( answer ) || {};
	const hasTitle = title?.length;
	const isDraft = ! hasTitle && ! answer && ! id;

	if ( isDraft ) return {};

	return {
		noTitle: ! hasTitle,
		...answerBlockValidation,
	};
};

/**
 * Get messages for the validation errors.
 *
 * @param {string} errors       Error codes.
 * @param {string} questionType Question type.
 * @return {string} Error message.
 */
export const getQuestionBlockValidationErrorMessages = (
	errors,
	questionType
) =>
	errors.map(
		( error ) =>
			types?.[ questionType ]?.messages?.[ error ] ||
			blockSettings.messages[ error ]
	);
