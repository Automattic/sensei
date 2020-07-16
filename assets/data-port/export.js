import { H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { render } from '@wordpress/element';
import { useMergeReducer } from '../react-hooks/use-merge-reducer';
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import { ExportProgressPage } from './export/export-progress-page';
import { ExportSelectContentPage } from './export/export-select-content-page';

/**
 * Sensei export page.
 */
const SenseiExportPage = () => {
	useSenseiColorTheme();

	const [ { inProgress, progress }, updateState ] = useMergeReducer( {
		inProgress: false,
		progress: null,
	} );

	const startExport = () => {
		updateState( { inProgress: true } );
	};

	return (
		<div className="sensei-page-export">
			<section className="sensei-data-port-step">
				<header className="sensei-data-port-step__header">
					<H>
						{ __( 'Export content to a CSV file', 'sensei-lms' ) }
					</H>
					<p>
						{ __(
							'This tool enables you to generate and download one or more CSV files containing a list of all courses, ' +
								'lessons and quizzes, or questions. Separate CSV files are generated for each content type.',
							'sensei-lms'
						) }
					</p>
				</header>
				{ inProgress ? (
					<ExportProgressPage state={ progress } />
				) : (
					<ExportSelectContentPage onSubmit={ startExport } />
				) }
			</section>
		</div>
	);
};

render( <SenseiExportPage />, document.getElementById( 'sensei-export-page' ) );
