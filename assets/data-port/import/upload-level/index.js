import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { UploadLevels } from './component';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			state: store.getStepData( 'upload' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { uploadFileForLevel, throwEarlyUploadError } = dispatch(
			'sensei/import'
		);

		return {
			uploadFileForLevel,
			throwEarlyUploadError,
		};
	} )
)( UploadLevels );
