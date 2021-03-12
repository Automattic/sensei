/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import {
	BLOCK_META_STORE,
	setBlockMeta,
} from '../../../shared/blocks/block-metadata';
import types from '../answer-blocks';
import blockSettings from '.';

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

	return getQuestionValidationErrors( {
		noTitle: ! hasTitle,
		...answerBlockValidation,
	} );
};

/**
 * Validate question block attributes.
 *
 * @param {Object} props
 * @param {string} props.clientId   Block ID.
 * @param {string} props.attributes Block attributes.
 * @return {Array} Validation errors.
 */
export const useQuestionValidation = ( { clientId, attributes } ) => {
	useEffect( () => {
		setBlockMeta( clientId, {
			validationErrors: validateQuestionBlock( attributes ),
		} );
	}, [ clientId, attributes ] );

	return useSelect(
		( select ) =>
			select( BLOCK_META_STORE ).getBlockMeta(
				clientId,
				'validationErrors'
			),
		[ clientId, attributes ]
	);
};

/**
 * Get errors from validation result.
 *
 * @param {Object} result Validation result.
 * @return {Array} Items that have failed validation.
 */
const getQuestionValidationErrors = ( result = {} ) =>
	Object.entries( result )
		.filter( ( [ , value ] ) => value )
		.map( ( [ key ] ) => key );

export const getValidationErrorMessage = ( error, questionType ) =>
	types?.[ questionType ]?.messages?.[ error ] ||
	blockSettings.messages[ error ];
