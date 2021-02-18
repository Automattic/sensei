/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { downloadFile } from '../../shared/helpers/download-file';
import { Notice } from '../notice';

/**
 * @typedef Job
 * @property {string}       status     Export status.
 * @property {number}       percentage Export progress percentage.
 * @property {ExportFile[]} files      Exported files.
 * @property {string}       error      Error message.
 */
/**
 * @typedef ExportFile
 * @property {string} url  File URL.
 * @property {string} name File name.
 */
/**
 * Exporter progress and result.
 *
 * @param {Object}   props
 * @param {Job}      props.job    Export job state.
 * @param {Function} props.reset  Function to return to initial export screen.
 * @param {Function} props.cancel Function to request job cancellation.
 */
export const ExportProgressPage = ( { job, reset, cancel } ) => {
	const { status, percentage, files, error } = job || {};

	const inProgress = 'completed' !== status;

	useEffect( () => {
		if ( inProgress || ! files ) return;
		files.forEach( downloadFile );
	}, [ files, inProgress ] );

	return (
		<section className="sensei-data-port-step__body">
			{ inProgress ? (
				<>
					<p>
						<progress
							className="sensei-data-port__progressbar"
							max="100"
							value={ percentage || 0 }
						/>
					</p>
					<div className="sensei-data-port-step__footer">
						<Button isPrimary onClick={ () => cancel() }>
							{ __( 'Cancel', 'sensei-lms' ) }
						</Button>
					</div>
				</>
			) : (
				<>
					<div className="sensei-export__output-result">
						{ files && (
							<>
								<p>
									{ _n(
										'The following file was exported:',
										'The following files were exported:',
										files.length,
										'sensei-lms'
									) }
								</p>
								<ul className="sensei-export__output-files">
									{ files.map( ( { name, url } ) => (
										<li
											className="sensei-export__output-file sensei-data-port-step__line"
											key={ name }
										>
											<a href={ url } download={ name }>
												{ name }
											</a>
										</li>
									) ) }
								</ul>
							</>
						) }
						{ error && (
							<div className="sensei-data-port-step__line">
								<Notice isError={ true } message={ error } />
							</div>
						) }
					</div>
					<div className="sensei-data-port-step__footer">
						<Button isPrimary onClick={ () => reset() }>
							{ __( 'Export More Content', 'sensei-lms' ) }
						</Button>
					</div>
				</>
			) }
		</section>
	);
};
