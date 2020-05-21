import { __ } from '@wordpress/i18n';
import { logEvent } from '../log_event';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.slug    Feature slug.
 * @param {string} props.excerpt Feature excerpt.
 * @param {string} [props.link]  Feature link.
 */
const FeatureDescription = ( { slug, excerpt, link } ) => (
	<>
		{ excerpt }
		{ link && (
			<>
				{ ' ' }
				<a
					className="sensei-onboarding__learn-more"
					href={ link }
					target="_blank"
					rel="noopener noreferrer"
					onClick={ () =>
						logEvent( 'sensei_setup_wizard_features_learn_more', {
							slug,
						} )
					}
				>
					{ __( 'Learn more', 'sensei-lms' ) }
				</a>
			</>
		) }
	</>
);

export default FeatureDescription;
