import { useStateManager } from '../../react-hooks/use-state-manager';
import { ExportStore } from './export-job';
import { ExportPage } from './export-page';

/**
 * Sensei export page data wrapper.
 */
export const SenseiExportPage = () => {
	const [ progress, { start, cancel, reset } ] = useStateManager(
		ExportStore
	);

	return <ExportPage { ...{ progress, start, cancel, reset } } />;
};
