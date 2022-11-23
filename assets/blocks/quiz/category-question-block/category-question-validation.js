/**
 * Internal dependencies
 */
import blockSettings from '.';

/**
 * Validate category question block.
 *
 * @param {Object} attributes Block attributes.
 * @return {Array} Validation errors.
 */
export const validateCategoryQuestionBlock = ( attributes ) => {
	const { options } = attributes;
	const hasCategory = options?.category > 0;

	return {
		noCategory: ! hasCategory,
	};
};

/**
 * Get messages for the validation errors.
 *
 * @param {string} errors Error codes.
 * @return {string} Error message.
 */
export const getCategoryQuestionBlockValidationErrorMessages = ( errors ) =>
	errors.map( ( error ) => blockSettings.messages[ error ] );
