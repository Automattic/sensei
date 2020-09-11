import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { ImportProgressPage } from './import-progress-page';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			state: store.getStepData( 'progress' ),
		};
	} )
)( ImportProgressPage );
