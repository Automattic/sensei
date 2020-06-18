import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { UploadPage } from './component';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			state: store.getStepData( 'upload' ),
			isReady: store.isReadyToStart(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { submitStartImport } = dispatch( 'sensei/import' );

		return {
			submitStartImport,
		};
	} )
)( UploadPage );
