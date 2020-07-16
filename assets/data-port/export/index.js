import { useMergeReducer } from '../../react-hooks/use-merge-reducer';
import { ExportPage } from './export-page';

/**
 * Sensei export page.
 */
export const SenseiExportPage = () => {
	const [ { progress }, updateState ] = useMergeReducer( {
		progress: null,
	} );

	const startExport = ( types ) => {
		updateState( {
			progress: {
				status: 'started',
				percentage: 0,
			},
		} );

		// Mock server updates.

		setTimeout(
			() =>
				updateState( {
					progress: {
						status: 'progress',
						percentage: 40,
					},
				} ),
			1000
		);
		setTimeout(
			() =>
				updateState( {
					progress: {
						status: 'completed',
						error: 'Lessons failed to export: No lesson found',
						files: types.map( ( t ) => ( {
							name: `${ t }.csv`,
							url:
								'/wp-content/uploads/2020/02/sample_tax_rates.csv',
						} ) ),
					},
				} ),
			2000
		);
	};

	const resetExport = () => {
		updateState( { inProgress: false, progress: null } );
	};

	return <ExportPage { ...{ progress, resetExport, startExport } } />;
};
