import { __ } from '@wordpress/i18n';
import { useSenseiColorTheme } from '../../react-hooks/use-sensei-color-theme';
import { ExportProgressPage } from './export-progress-page';
import { ExportSelectContentPage } from './export-select-content-page';

/**
 * Export page.
 *
 * @param {Object} props
 * @param {Object} props.progress
 * @param {Function} props.startExport
 * @param {Function} props.resetExport
 */
export const ExportPage = ( { progress, startExport, resetExport } ) => {
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
								'lessons and quizzes, or questions. Separate CSV files are generated for each content type.',
							'sensei-lms'
						) }
					</p>
				</header>
				{ progress ? (
					<ExportProgressPage
						state={ progress }
						reset={ resetExport }
					/>
				) : (
					<ExportSelectContentPage onSubmit={ startExport } />
				) }
			</section>
		</div>
	);
};
