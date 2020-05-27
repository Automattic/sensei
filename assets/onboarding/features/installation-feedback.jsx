import { useState, useEffect } from '@wordpress/element';
import { List } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';
import FeatureStatus, {
	INSTALLING_STATUS,
	ERROR_STATUS,
} from './feature-status';
import useFeaturesPolling from './use-features-polling';

const getStatus = ( status = INSTALLING_STATUS ) => status;

/**
 * Installation feedback component.
 *
 * @param {Object}    props
 * @param {Function}  props.onContinue Callback to continue to the next step.
 * @param {Function}  props.onRetry    Callback to retry installations.
 */
const InstallationFeedback = ( { onContinue, onRetry } ) => {
	const [ hasInstalling, setHasInstalling ] = useState( true );
	const [ hasError, setHasError ] = useState( false );

	// Polling is active while some feature is installing.
	const featuresData = useFeaturesPolling( hasInstalling );
	const features = featuresData.options.filter( ( feature ) =>
		featuresData.selected.includes( feature.slug )
	);

	// Update general statuses when features is updated.
	useEffect( () => {
		setHasInstalling(
			features.some(
				( feature ) => getStatus( feature.status ) === INSTALLING_STATUS
			)
		);

		setHasError(
			features.some(
				( feature ) => getStatus( feature.status ) === ERROR_STATUS
			)
		);
	}, [ features ] );

	let actionButtons;

	if ( hasInstalling ) {
		actionButtons = (
			<Button
				isPrimary
				isBusy
				disabled
				className="sensei-onboarding__button"
			>
				{ __( 'Installing…', 'sensei-lms' ) }
			</Button>
		);
	} else if ( hasError ) {
		const onRetryAll = () => {
			onRetry(
				features
					.filter(
						( feature ) =>
							getStatus( feature.status ) === ERROR_STATUS
					)
					.map( ( feature ) => feature.slug )
			);
		};
		actionButtons = (
			<>
				<Button
					isPrimary
					className="sensei-onboarding__button"
					onClick={ onRetryAll }
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
					( { slug, title, excerpt, link, error, status } ) => ( {
						title,
						content: (
							<FeatureDescription
								excerpt={ excerpt }
								link={ link }
								error={ error }
								onFeatureRetry={ () => onRetry( [ slug ] ) }
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
