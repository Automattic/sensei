import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeaturesList, { LOADING_STATUS, ERROR_STATUS } from './features-list';

/**
 * @typedef  {Object} Feature
 * @property {string} id              Feature ID.
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
		<div>
			<FeaturesList className="no-last-line">
				{ features.map(
					( {
						id,
						title,
						description,
						learnMoreLink,
						errorMessage,
						status,
					} ) => (
						<FeaturesList.Item
							key={ id }
							title={ title }
							description={ description }
							learnMoreLink={ learnMoreLink }
							errorMessage={ errorMessage }
							onFeatureRetry={ () => {} }
							status={ status }
						/>
					)
				) }
			</FeaturesList>
			<div className="sensei-onboarding__group-buttons group-center">
				{ actionButtons }
			</div>
		</div>
	);
};

export default InstallationFeedback;
