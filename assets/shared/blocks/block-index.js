/**
 * WordPress dependencies
 */
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
			let number = 0;
			const store = select( 'core/block-editor' );
			const blocks = store.getBlocks(
				store.getBlockRootClientId( clientId )
			);

			blocks.every( ( block ) => {
				number++;

				if ( block.clientId === clientId ) {
					return false;
				}

				if ( block.name === 'sensei-lms/quiz-category-question' ) {
					number += block.attributes.options?.number - 1 ?? 0;
				}

				return true;
			} );

			return number;
		},
		[ clientId ]
	);
