/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Get the current number (order) of the question block in a quiz.
 *
 * @param {string} clientId Block Client Id.
 * @return {number} Block index
 */
export const useQuestionNumber = ( clientId ) => {
	const blocksBefore = useSelect(
		( select ) => {
			const store = select( 'core/block-editor' );
			const rootClientId = store.getBlockRootClientId( clientId );
			const blocks = store.getBlocks( rootClientId );

			return ( blocks || [] ).slice(
				0,
				store.getBlockIndex( clientId, rootClientId )
			);
		},
		[ clientId ]
	);

	const questionCount = blocksBefore.reduce(
		( count, question ) =>
			count +
			( question.attributes.type === 'category-question'
				? question.attributes.options?.number || 1
				: 1 ),
		0
	);

	return questionCount + 1;
};
