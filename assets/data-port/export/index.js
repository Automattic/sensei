import { useStateManager } from '../../react-hooks/use-state-manager';
import { ExportJobState } from './export-job-state';
import { ExportPage } from './export-page';

/**
 * Sensei export page data wrapper.
 */
export const SenseiExportPage = () => {
	const [ progress, { start, cancel, reset } ] = useStateManager(
		ExportJobState
	);

	return <ExportPage { ...{ progress, start, cancel, reset } } />;
};
