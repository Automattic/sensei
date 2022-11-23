/**
 * WordPress dependencies
 */
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { UploadLevels } from './upload-level';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			jobId: store.getJobId(),
			state: store.getStepData( 'upload' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			deleteLevelFile,
			uploadFileForLevel,
			throwEarlyUploadError,
		} = dispatch( 'sensei/import' );

		return {
			deleteLevelFile,
			uploadFileForLevel,
			throwEarlyUploadError,
		};
	} )
)( UploadLevels );
