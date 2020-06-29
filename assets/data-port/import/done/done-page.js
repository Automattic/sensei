import { __ } from '@wordpress/i18n';
import { Section } from '@woocommerce/components';
import { useEffect } from '@wordpress/element';
import { formatString } from '../../../setup-wizard/helpers/format-string';
import { Button, Dashicon } from '@wordpress/components';
import { ImportLog } from './import-log';
import { groupResults, ImportResults } from './import-results';

/**
 * Done page of the importer.
 */
export const DonePage = ( {
	restartImporter,
	results,
	logs,
	fetchImportLog,
} ) => {
	const { success: resultSuccess, error: resultErrors } = groupResults(
		results
	);

	useEffect( () => {
		fetchImportLog();
	}, [ fetchImportLog ] );

	const hasErrors = resultErrors.some( ( [ , errors ] ) => errors );

	return (
		<section className="sensei-data-port-step sensei-import-done">
			<Section className="sensei-data-port-step__body">
				<section className="sensei-import-done__result">
					{ hasErrors ? (
						<Dashicon
							icon="warning"
							size={ 150 }
							className="sensei-import-done__result-icon--warning"
						/>
					) : (
						<Dashicon
							icon="yes-alt"
							size={ 150 }
							className="sensei-import-done__result-icon--success"
						/>
					) }
					<p>
						{ __(
							'The following content was imported:',
							'sensei-lms'
						) }
					</p>
					<ImportResults
						entries={ resultSuccess }
						showLink={ true }
					/>
				</section>
				{ hasErrors && (
					<>
						<section className="sensei-import-done__errors">
							<p>
								{ formatString(
									__(
										'The following content {{strong}}failed{{/strong}} to import:',
										'sensei-lms'
									)
								) }
							</p>
							<ImportResults
								entries={ resultErrors }
								showLink={ false }
							/>
						</section>
						<section className="sensei-import-done__log">
							<ImportLog result={ logs } />
						</section>
					</>
				) }

				<div className="continue-container sensei-import-footer">
					<Button isPrimary onClick={ restartImporter }>
						{ __( 'Import More Content', 'sensei-lms' ) }
					</Button>
				</div>
			</Section>
		</section>
	);
};
