import { List } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';
import FeatureStatus, {
	INSTALLING_STATUS,
	ERROR_STATUS,
} from './feature-status';

const getStatus = ( status = INSTALLING_STATUS ) => status;

/**
 * @typedef  {Object} Feature
 * @property {string} title   Feature title.
 * @property {string} excerpt Feature excerpt.
 * @property {string} [link]  Feature link.
 * @property {string} [error] Error message.
 * @property {string} status  Feature status.
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
		( feature ) => getStatus( feature.status ) === INSTALLING_STATUS
	);

	const hasError = features.some(
		( feature ) => getStatus( feature.status ) === ERROR_STATUS
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
					( { title, excerpt, link, error, status } ) => ( {
						title,
						content: (
							<FeatureDescription
								excerpt={ excerpt }
								link={ link }
								error={ error }
								onFeatureRetry={ () => {} }
							/>
						),
						before: (
							<FeatureStatus status={ getStatus( status ) } />
						),
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
