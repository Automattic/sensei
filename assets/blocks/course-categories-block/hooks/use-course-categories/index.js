/**
 * WordPress dependencies
 */
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

// Heavily inspired from here https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/post-terms/use-post-terms.js
const usePostTerms = ( { postId, postType, term } ) => {
	const { rest_base: restBase, slug } = term;
	const [ termIds ] = useEntityProp( 'postType', postType, restBase, postId );

	return useSelect(
		( select ) => {
			const visible = term?.visibility?.publicly_queryable;
			if ( ! visible ) {
				return {
					postTerms: [],
					isLoading: false,
					hasPostTerms: false,
				};
			}
			if ( ! termIds ) {
				// Waiting for post terms to be fetched.
				return { isLoading: term?.postTerms?.includes( postType ) };
			}
			if ( ! termIds.length ) {
				return { isLoading: false };
			}

			const { getEntityRecords, isResolving } = select( coreStore );
			const taxonomyArgs = [
				'taxonomy',
				slug,
				{
					include: termIds,
					context: 'view',
				},
			];
			const terms = getEntityRecords( ...taxonomyArgs );
			const _isLoading = isResolving( 'getEntityRecords', taxonomyArgs );
			return {
				postTerms: terms,
				isLoading: _isLoading,
				hasPostTerms: !! terms?.length,
			};
		},
		[ termIds, term?.visibility?.publicly_queryable ]
	);
};

export default function useCourseCategories( postId ) {
	const selectedTerm = useSelect( ( select ) => {
		const { getTaxonomy } = select( coreStore );
		const taxonomy = getTaxonomy( 'course-category' );
		return taxonomy?.visibility?.publicly_queryable ? taxonomy : {};
	} );

	return usePostTerms( { postId, postType: 'course', term: selectedTerm } );
}
