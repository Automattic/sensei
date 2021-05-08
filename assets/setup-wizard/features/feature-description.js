/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.slug          Feature slug.
 * @param {string} props.excerpt       Feature excerpt.
 * @param {string} [props.link]        Feature link.
 * @param {string} [props.observation] Feature observation.
 */
const FeatureDescription = ( { slug, excerpt, link, observation } ) => (
	<>
		{ excerpt }
		{ link && (
			<>
				{ ' ' }
				<a
					className="sensei-setup-wizard__learn-more link__color-primary"
					href={ link }
					target="_blank"
					rel="noopener noreferrer"
					{ ...logLink( 'setup_wizard_features_learn_more', {
						slug,
					} ) }
				>
					{ __( 'Learn more', 'sensei-lms' ) }
				</a>
			</>
		) }
		{ observation && (
			<em className="sensei-setup-wizard__feature-observation">
				{ observation }
			</em>
		) }
	</>
);

export default FeatureDescription;
