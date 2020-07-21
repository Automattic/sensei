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

	const { start, cancel, reset } = exportJob;

	if ( initializing ) {
		return (
			<div className="sensei-import__main-loader">
				<Spinner />
			</div>
		);
	}

	return <ExportPage { ...{ progress, start, cancel, reset } } />;
};
