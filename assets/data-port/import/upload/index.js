/**
 * WordPress dependencies
 */
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { UploadPage } from './upload-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			state: store.getStepData( 'upload' ),
			isReady: store.isReadyToStart(),
		};
	} ),
	withDispatch( ( dispatch, ownProps, { select } ) => {
		const { submitStartImport } = dispatch( 'sensei/import' );

		return {
			submitStartImport: () => {
				submitStartImport( select( 'sensei/import' ).getJobId() );

				// Log continue to import from uploaded files.
				const type = select( 'sensei/import' )
					.getUploadedLevelKeys()
					.join( ',' );
				window.sensei_log_event( 'import_continue_click', { type } );
			},
		};
	} )
)( UploadPage );
