import { __ } from '@wordpress/i18n';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.excerpt Feature excerpt.
 * @param {string} [props.link]  Feature link.
 */
const FeatureDescription = ( { excerpt, link } ) => (
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
				>
					{ __( 'Learn more', 'sensei-lms' ) }
				</a>
			</>
		) }
	</>
);

export default FeatureDescription;
