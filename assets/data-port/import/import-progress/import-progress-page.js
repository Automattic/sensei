/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useProgressPolling from './use-progress-polling';
import { H, Section } from '../../../shared/components/section';

/**
 * This component displays the import progress page of the importer.
 *
 * @param {Object} input       ImportProgressPage input.
 * @param {Object} input.state The import state.
 */
export const ImportProgressPage = ( { state } ) => {
	const { percentage } = state;

	useProgressPolling();

	return (
		<section className="sensei-data-port-step sensei-import-progress-page">
			<header className="sensei-data-port-step__header">
				<H>{ __( 'Importing', 'sensei-lms' ) }</H>
				<p>
					{ __(
						'Your content is now being imported…',
						'sensei-lms'
					) }
				</p>
			</header>
			<Section
				className="sensei-data-port-step__body"
				component="section"
			>
				<p>
					<progress
						className="sensei-data-port__progressbar sensei-import-progress-page__progress"
						max="100"
						value={ percentage }
					/>
				</p>
			</Section>
		</section>
	);
};
