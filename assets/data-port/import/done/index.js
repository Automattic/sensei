import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { DonePage } from './done-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		const { results, logs } = store.getStepData( 'done' );
		return {
			results,
			logs,
		};
	} ),
	withDispatch( ( dispatch, ownProps, { select } ) => {
		const { fetchImportLog, restart } = dispatch( 'sensei/import' );

		return {
			fetchImportLog: () =>
				fetchImportLog( select( 'sensei/import' ).getJobId() ),
			resetState: restart,
		};
	} )
)( DonePage );
