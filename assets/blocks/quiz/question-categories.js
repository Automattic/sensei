/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Get the question categories.
 *
 * @return {Object[]} Term objects.
 */
export const useQuestionCategories = () => {
	const questionCategories = useSelect( ( select ) => {
		const terms = select( 'core' ).getEntityRecords(
			'taxonomy',
			'question-category',
			{
				per_page: -1,
			}
		);

		if ( terms && terms.length ) {
			return terms.map( ( term ) => ( {
				...term,
				name: unescape( term.name ),
			} ) );
		}

		return terms;
	} );

	const getById = ( termId ) => {
		if ( ! questionCategories || questionCategories.length === 0 ) {
			return false;
		}

		return questionCategories.find( ( term ) => term.id === termId );
	};

	return [ questionCategories, getById ];
};
