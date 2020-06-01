import { __ } from '@wordpress/i18n';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.excerpt          Feature excerpt.
 * @param {string} [props.link]           Feature link.
 * @param {string} [props.errorMessage]   Error message.
 * @param {string} [props.onFeatureRetry] Retry feature installation callback.
 */
const FeatureDescription = ( {
	excerpt,
	link,
	errorMessage,
	onFeatureRetry,
} ) => (
	<>
		{ excerpt }
		{ link && (
			<>
				{ ' ' }
				<a
					className="sensei-onboarding__learn-more link__color-primary"
					href={ link }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Learn more', 'sensei-lms' ) }
				</a>
			</>
		) }

		{ errorMessage && (
			<p className="sensei-onboarding__error-message">
				{ errorMessage }
				{ onFeatureRetry && (
					<>
						{ ' ' }
						<button
							className="sensei-onboarding__retry-button"
							type="button"
							onClick={ onFeatureRetry }
						>
							{ __( 'Retry?', 'sensei-lms' ) }
						</button>
					</>
				) }
			</p>
		) }
	</>
);

export default FeatureDescription;
