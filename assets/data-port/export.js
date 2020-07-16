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

	const startExport = ( types ) => {
		updateState( { inProgress: true } );

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
