import { Spinner } from '@wordpress/components';
import { useMergeReducer } from '../../react-hooks/use-merge-reducer';
import { ExportJob } from './export-job';
import { ExportPage } from './export-page';
import { useMemo, useEffect } from '@wordpress/element';

/**
 * Sensei export page data wrapper.
 */
export const SenseiExportPage = () => {
	const [ { initializing, progress }, updateState ] = useMergeReducer( {
		progress: null,
		initializing: true,
	} );

	/**
	 * Export API client.
	 *
	 * @type {ExportJob}
	 */
	const exportJob = useMemo(
		() =>
			new ExportJob( ( jobState ) =>
				updateState( { progress: jobState } )
			),
		[ updateState ]
	);

	useEffect( () => {
		exportJob.update().then( () => updateState( { initializing: false } ) );
	}, [ exportJob, updateState ] );

	/**
	 * Start exporting.
	 *
	 * @param {string[]} types Content types.
	 */
	const startExport = ( types ) => exportJob.start( types );

	/**
	 * Cancel current export
	 */
	const cancelExport = () => exportJob.cancel();

	/**
	 * Reset export page to content type selection screen.
	 */
	const resetExport = () => {
		updateState( { progress: null } );
	};

	if ( initializing ) {
		return (
			<div className="sensei-import__main-loader">
				<Spinner />
			</div>
		);
	}

	return (
		<ExportPage
			{ ...{ progress, resetExport, startExport, cancelExport } }
		/>
	);
};
