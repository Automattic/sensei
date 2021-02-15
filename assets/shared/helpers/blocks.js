import { useSelect } from '@wordpress/data';

/**
 * Are the block or any of it's descendants selected.
 *
 * @param {Object}  props
 * @param {boolean} props.isSelected Block selected
 * @param {string}  props.clientId   Block ID
 * @return {boolean} Selection state
 */
export const useHasSelected = ( { isSelected, clientId } ) => {
	return (
		useSelect(
			( select ) =>
				select( 'core/block-editor' ).hasSelectedInnerBlock( clientId ),
			[ clientId ]
		) || isSelected
	);
};
