import { H, Section } from '@woocommerce/components';
import useProgressPolling from './use-progress-polling';
import { __ } from '@wordpress/i18n';

/**
 * This component displays the import progress page of the importer.
 */
export const ImportProgressPage = ( { jobId, state } ) => {
	const { status, percentage } = state;
	const isActive = status !== 'completed';

	useProgressPolling( isActive, jobId );

	return (
		<section className="sensei-import-step sensei-import-progress-page">
			<header className="sensei-import-step__header">
				<H>{ __( 'Importing', 'sensei-lms' ) }</H>
				<p>
					{ __(
						'Your content is now being imported…',
						'sensei-lms'
					) }
				</p>
			</header>
			<Section className="sensei-import-step__body" component="section">
				<p>
					<progress
						className="sensei-import-progress-page__progress"
						max="100"
						value={ percentage }
					/>
				</p>
			</Section>
		</section>
	);
};
