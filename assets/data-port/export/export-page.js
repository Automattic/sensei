/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../../react-hooks/use-sensei-color-theme';
import { Notice } from '@wordpress/components';
import { ExportProgressPage } from './export-progress-page';
import { ExportSelectContentPage } from './export-select-content-page';

/**
 * Export page.
 *
 * @param {Object}   props
 * @param {Object}   props.job
 * @param {Object}   props.error
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
					<h2>
						{ __( 'Export content to a CSV file', 'sensei-lms' ) }
					</h2>
					<p>
						{ __(
							'This tool enables you to export courses, lessons, and questions to CSV files. ' +
								'These files are bundled together and downloaded to your computer in .zip format.',
							'sensei-lms'
						) }
					</p>
				</header>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ job && 'creating' !== job.status ? (
					<ExportProgressPage { ...{ job, reset, cancel } } />
				) : (
					<ExportSelectContentPage onSubmit={ start } job={ job } />
				) }
			</section>
		</div>
	);
};
