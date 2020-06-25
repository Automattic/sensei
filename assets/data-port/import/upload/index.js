import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { UploadPage } from './upload-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			state: store.getStepData( 'upload' ),
			isReady: store.isReadyToStart(),
			jobId: store.getJobId(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { submitStartImport } = dispatch( 'sensei/import' );

		return {
			submitStartImport,
		};
	} )
)( UploadPage );
