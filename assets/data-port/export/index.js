import { ExportPage } from './export-page';
import registerExportStore, { EXPORT_STORE } from './store';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

registerExportStore();

export default compose(
	withSelect( ( select ) => {
		return {
			job: select( EXPORT_STORE ).getJob(),
			error: select( EXPORT_STORE ).getRequestError(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { start, cancel, reset } = dispatch( EXPORT_STORE );
		return { start, cancel, reset };
	} )
)( ExportPage );
