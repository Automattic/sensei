import { __ } from '@wordpress/i18n';
import { useSenseiColorTheme } from '../../react-hooks/use-sensei-color-theme';
import { Notice } from '@wordpress/components';
import { ExportProgressPage } from './export-progress-page';
import { ExportSelectContentPage } from './export-select-content-page';

/**
 * Export page.
 *
 * @param {Object} props
 * @param {Object} props.job
 * @param {Object} props.error
 * @param {Function} props.start
 * @param {Function} props.reset
 * @param {Function} props.cancel
 */
export const ExportPage = ( { job, error, start, reset, cancel } ) => {
	useSenseiColorTheme();

	return (
		<div className="sensei-page-export">
			<section className="sensei-data-port-step">
				<header className="sensei-data-port-step__header">
					<h1>
						{ __( 'Export content to a CSV file', 'sensei-lms' ) }
					</h1>
					<p>
						{ __(
							'This tool enables you to generate and download one or more CSV files containing a list of all courses, ' +
								'lessons, or questions. Separate CSV files are generated for each content type.',
							'sensei-lms'
						) }
					</p>
				</header>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ job ? (
					<ExportProgressPage { ...{ job, reset, cancel } } />
				) : (
					<ExportSelectContentPage onSubmit={ start } />
				) }
			</section>
		</div>
	);
};
