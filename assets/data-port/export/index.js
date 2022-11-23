/**
 * WordPress dependencies
 */
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { ExportPage } from './export-page';
import registerExportStore, { EXPORT_STORE } from './store';

registerExportStore();

export default compose(
	withSelect( ( select ) => {
		return {
			job: select( EXPORT_STORE ).getJob(),
			error: select( EXPORT_STORE ).getError(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { start, cancel, reset } = dispatch( EXPORT_STORE );
		return { start, cancel, reset };
	} )
)( ExportPage );
