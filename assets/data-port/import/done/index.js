import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { DonePage } from './done-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );
		const jobId = store.getJobId();

		return {
			successResults: store.getSuccessResults(),
			logs: store.getLogsBySeverity( jobId ),
			isFetching: store.isResolving( 'getLogsBySeverity', [ jobId ] ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { restartImporter } = dispatch( 'sensei/import' );

		return {
			restartImporter,
		};
	} )
)( DonePage );
