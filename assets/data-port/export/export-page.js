/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../../react-hooks/use-sensei-color-theme';
import { Notice } from '@wordpress/components';
import { ExportProgressPage } from './export-progress-page';
import { ExportSelectContentPage } from './export-select-content-page';
import { ExportSelectItemsPage } from './export-select-items-page';

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

	const [ selectedTypes, setSelectedTypes ] = useState( null );

	const renderSetupStep = () => {
		if ( selectedTypes === null ) {
			return (
				<ExportSelectContentPage
					onSubmit={ ( types ) => setSelectedTypes( types ) }
					job={ job }
				/>
			);
		}

		return (
			<ExportSelectItemsPage
				types={ selectedTypes }
				job={ job }
				onSubmit={ ( { types, selections } ) =>
					start( types, selections )
				}
				onBack={ () => setSelectedTypes( null ) }
			/>
		);
	};

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
					renderSetupStep()
				) }
			</section>
		</div>
	);
};
