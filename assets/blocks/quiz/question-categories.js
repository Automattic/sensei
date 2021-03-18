/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * External dependencies
 */
import { keyBy } from 'lodash';

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

		return terms ?? [];
	}, [] );

	const unescapedQuestionCategories = useMemo(
		() =>
			( questionCategories || [] ).map( ( term ) => ( {
				...term,
				name: unescape( term.name ),
			} ) ),
		[ questionCategories ]
	);

	const questionCategoriesById = useMemo(
		() => keyBy( unescapedQuestionCategories ?? [], 'id' ),
		[ unescapedQuestionCategories ]
	);

	const getById = ( termId ) => {
		if ( ! questionCategoriesById || questionCategoriesById.length === 0 ) {
			return false;
		}

		return questionCategoriesById[ termId ] ?? false;
	};

	return [ unescapedQuestionCategories, getById ];
};
