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
import { ExportModePickerPage } from './export-mode-picker-page';
import { ExportByCoursePage } from './export-by-course-page';
import { ExportByFileTypePage } from './export-by-file-type-page';

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

	const [ mode, setMode ] = useState( null );

	const onSubmit = ( { types, selections, mode: chosenMode } ) =>
		start( types, selections, chosenMode );

	const renderSetupStep = () => {
		if ( mode === null ) {
			return <ExportModePickerPage onPickMode={ setMode } />;
		}

		if ( mode === 'by_course' ) {
			return (
				<ExportByCoursePage
					job={ job }
					onSubmit={ onSubmit }
					onBack={ () => setMode( null ) }
				/>
			);
		}

		return (
			<ExportByFileTypePage
				job={ job }
				onSubmit={ onSubmit }
				onBack={ () => setMode( null ) }
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
