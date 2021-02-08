import { useSelect } from '@wordpress/data';

/**
 * Get the current index (order) of the block in the block list.
 *
 * @param {string} clientId Block Client Id.
 * @return {number} Block index
 */
export const useBlockIndex = ( clientId ) =>
	useSelect(
		( select ) => {
			const store = select( 'core/block-editor' );
			return store.getBlockIndex(
				clientId,
				store.getBlockRootClientId( clientId )
			);
		},
		[ clientId ]
	);
