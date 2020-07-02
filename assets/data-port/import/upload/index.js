import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { UploadPage } from './upload-page';
import { partial } from 'lodash';

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
			submitStartImport: partial(
				submitStartImport,
				select( 'sensei/import' ).getJobId()
			),
		};
	} )
)( UploadPage );
