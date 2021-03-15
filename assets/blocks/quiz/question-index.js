/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Get the current index (order) of the question block in a quiz.
 *
 * @param {string} clientId Block Client Id.
 * @return {number} Block index
 */
export const useQuestionIndex = ( clientId ) => {
	const blocks = useSelect(
		( select ) => {
			const store = select( 'core/block-editor' );
			return store.getBlocks( store.getBlockRootClientId( clientId ) );
		},
		[ clientId ]
	);

	let number = 0;
	if ( ! blocks || blocks.length === 0 ) {
		return 1;
	}

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
};
