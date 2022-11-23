/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Validate block attributes. Requires block metadata.
 *
 * @param {Function} validateBlock    Validate function.
 * @param {Object}   props
 * @param {string}   props.attributes Block attributes.
 * @param {Function} props.setMeta    Block meta setter.
 */
export const useBlockValidation = (
	validateBlock,
	{ attributes, setMeta }
) => {
	useEffect( () => {
		const validationErrors = getValidationErrors(
			validateBlock( attributes )
		);
		setMeta( {
			validationErrors,
		} );
	}, [ validateBlock, attributes, setMeta ] );
};

/**
 * Validate block attributes. Requires block metadata.
 *
 * @param {Function} validateBlock Validate function.
 */
export const withBlockValidation = ( validateBlock ) =>
	createHigherOrderComponent( ( Block ) => {
		return ( props ) => {
			useBlockValidation( validateBlock, props );
			return <Block { ...props } />;
		};
	}, 'withBlockValidation' );

/**
 * Get errors from validation result.
 *
 * @param {Object} result Validation result.
 * @return {Array} Items that have failed validation.
 */
const getValidationErrors = ( result = {} ) =>
	Object.entries( result )
		.filter( ( [ , value ] ) => value )
		.map( ( [ key ] ) => key );
