import { dispatch, useSelect } from '@wordpress/data';

/**
 * A custom hook which passes the block attributes to its' inner blocks. By using this hook the inner blocks will
 * be supplied with two more attributes, parentAttributes and parentSetAttributes.
 *
 * @param {string}   clientId      Block client ID.
 * @param {Object}   attributes    The block attributes.
 * @param {Function} setAttributes Callback method to change the value of the parent's attributes.
 */
export function useDescendantAttributes( clientId, attributes, setAttributes ) {
	const childBlocks = useSelect( ( select ) => {
		return select( 'core/editor' ).getBlocksByClientId( clientId )[ 0 ]
			.innerBlocks;
	} );

	childBlocks.forEach( ( childBlock ) => {
		dispatch( 'core/editor' ).updateBlockAttributes( childBlock.clientId, {
			parentAttributes: attributes,
			parentSetAttributes: setAttributes,
		} );
	} );
}
