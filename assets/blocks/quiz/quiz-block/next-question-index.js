/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isQuestionEmpty } from '../data';

/**
 * Get the next question index.
 *
 * @param {string} clientId The client ID.
 *
 * @return {number} Next question index.
 */
export const useNextQuestionIndex = ( clientId ) => {
	const questionBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[]
	);

	const lastBlock =
		questionBlocks.length && questionBlocks[ questionBlocks.length - 1 ];

	const hasEmptyLastBlock =
		lastBlock && isQuestionEmpty( lastBlock.attributes );

	return hasEmptyLastBlock
		? questionBlocks.length - 1
		: questionBlocks.length;
};
