/**
 * WordPress dependencies
 */
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { DonePage } from './done-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );
		const jobId = store.getJobId();

		return {
			successResults: store.getSuccessResults(),
			logs: store.getLogsBySeverity( jobId ),
			isFetching: store.isResolving( 'getLogsBySeverity', [ jobId ] ),
			fetchError: store.getLogsFetchError(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			restartImporter,
			invalidateResolutionForStoreSelector,
		} = dispatch( 'sensei/import' );

		return {
			restartImporter,
			retry: () =>
				invalidateResolutionForStoreSelector( 'getLogsBySeverity' ),
		};
	} )
)( DonePage );
