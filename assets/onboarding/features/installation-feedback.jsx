import { List } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';
import FeatureStatus, { LOADING_STATUS, ERROR_STATUS } from './feature-status';

/**
 * @typedef  {Object} Feature
 * @property {string} title           Feature title.
 * @property {string} description     Feature description.
 * @property {string} [learnMoreLink] Learn more link.
 * @property {string} [errorMessage]  Error message.
 * @property {string} status          Feature status.
 */
/**
 * Installation feedback component.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features   Features list.
 * @param {Function}  props.onContinue Callback to continue to the next step.
 */
const InstallationFeedback = ( { features, onContinue } ) => {
	const hasLoading = features.some(
		( feature ) => feature.status === LOADING_STATUS
	);

	const hasError = features.some(
		( feature ) => feature.status === ERROR_STATUS
	);

	let actionButtons;

	if ( hasLoading ) {
		actionButtons = (
			<Button isPrimary className="sensei-onboarding__button">
				{ __( 'Installing…', 'sensei-lms' ) }
			</Button>
		);
	} else if ( hasError ) {
		actionButtons = (
			<>
				<Button
					isPrimary
					className="sensei-onboarding__button"
					onClick={ () => {} }
				>
					{ __( 'Retry', 'sensei-lms' ) }
				</Button>
				<Button
					isSecondary
					className="sensei-onboarding__button"
					onClick={ onContinue }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</>
		);
	} else {
		actionButtons = (
			<Button
				isPrimary
				className="sensei-onboarding__button"
				onClick={ onContinue }
			>
				{ __( 'Continue', 'sensei-lms' ) }
			</Button>
		);
	}

	return (
		<div className="sensei-onboarding__features-installation-feedback">
			<List
				items={ features.map(
					( {
						title,
						description,
						learnMoreLink,
						errorMessage,
						status,
					} ) => ( {
						title,
						content: (
							<FeatureDescription
								description={ description }
								learnMoreLink={ learnMoreLink }
								errorMessage={ errorMessage }
								onFeatureRetry={ () => {} }
							/>
						),
						before: <FeatureStatus status={ status } />,
					} )
				) }
			/>
			<div className="sensei-onboarding__group-buttons group-center">
				{ actionButtons }
			</div>
		</div>
	);
};

export default InstallationFeedback;
