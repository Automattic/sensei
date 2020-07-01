import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { DonePage } from './done-page';
import { partial } from 'lodash';

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
		const { fetchImportLog, restartImporter } = dispatch( 'sensei/import' );

		return {
			fetchImportLog: partial(
				fetchImportLog,
				select( 'sensei/import' ).getJobId()
			),
			restartImporter,
		};
	} )
)( DonePage );
