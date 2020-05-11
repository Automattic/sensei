import { __ } from '@wordpress/i18n';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.description                    Feature description.
 * @param {string} [props.confirmationExtraDescription] Extra description that appears only in confirmation modal.
 * @param {string} [props.learnMoreLink]                Learn more link.
 */
const FeatureDescription = ( {
	description,
	confirmationExtraDescription,
	learnMoreLink,
} ) => (
	<>
		{ description } { confirmationExtraDescription }
		{ learnMoreLink && (
			<>
				{ ' ' }
				<a
					className="sensei-onboarding__learn-more"
					href={ learnMoreLink }
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
