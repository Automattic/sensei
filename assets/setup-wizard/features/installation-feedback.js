/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getFeatureObservation } from './feature-description-utils';
import FeatureDescription from './feature-description';
import FeatureStatus, {
	INSTALLING_STATUS,
	ERROR_STATUS,
	EXTERNAL_STATUS,
} from './feature-status';
import useFeaturesPolling from './use-features-polling';
import List from '../../shared/components/list';

/**
 * Installation feedback component.
 *
 * @param {Object}   props
 * @param {Function} props.onContinue Callback to continue to the next step.
 * @param {Function} props.onRetry    Callback to retry installations.
 */
const InstallationFeedback = ( { onContinue, onRetry } ) => {
	const [ hasInstalling, setHasInstalling ] = useState( true );
	const [ isPolling, setIsPolling ] = useState( true );
	const [ hasError, setHasError ] = useState( false );

	// Polling is active while some feature is installing.
	const featuresData = useFeaturesPolling( isPolling );
	const features = featuresData.options.filter( ( feature ) =>
		featuresData.selected.includes( feature.slug )
	);

	// Update general statuses when features is updated.
	useEffect( () => {
		setHasInstalling(
			features.some( ( feature ) => feature.status === INSTALLING_STATUS )
		);
		setIsPolling(
			features.some( ( feature ) =>
				[ INSTALLING_STATUS, EXTERNAL_STATUS ].includes(
					feature.status
				)
			)
		);

		setHasError(
			features.some( ( feature ) => feature.status === ERROR_STATUS )
		);
	}, [ features ] );

	let actionButtons;

	if ( hasInstalling ) {
		actionButtons = (
			<Button
				isPrimary
				isBusy
				disabled
				className="sensei-setup-wizard__button"
			>
				{ __( 'Installing…', 'sensei-lms' ) }
			</Button>
		);
	} else if ( hasError ) {
		const onRetryAll = () => {
			onRetry(
				features
					.filter( ( feature ) => feature.status === ERROR_STATUS )
					.map( ( feature ) => feature.slug )
			);
		};
		actionButtons = (
			<>
				<Button
					isPrimary
					className="sensei-setup-wizard__button"
					onClick={ onRetryAll }
				>
					{ __( 'Retry', 'sensei-lms' ) }
				</Button>
				<Button
					isSecondary
					className="sensei-setup-wizard__button"
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
				className="sensei-setup-wizard__button"
				onClick={ onContinue }
			>
				{ __( 'Continue', 'sensei-lms' ) }
			</Button>
		);
	}

	return (
		<div className="sensei-setup-wizard__features-installation-feedback">
			<List
				items={ features.map(
					( { slug, title, excerpt, link, error, status } ) => ( {
						title,
						content: (
							<>
								<FeatureDescription
									slug={ slug }
									excerpt={ excerpt }
									link={ link }
									observation={ getFeatureObservation(
										slug,
										features
									) }
								/>
								{ error && (
									<p className="sensei-setup-wizard__error-message">
										{ error }{ ' ' }
										<button
											className="sensei-setup-wizard__retry-button"
											type="button"
											onClick={ () =>
												onRetry( [ slug ] )
											}
										>
											{ __( 'Retry?', 'sensei-lms' ) }
										</button>
									</p>
								) }
							</>
						),
						before: <FeatureStatus status={ status } />,
					} )
				) }
			/>
			<div className="sensei-setup-wizard__group-buttons group-center">
				{ actionButtons }
			</div>
		</div>
	);
};

export default InstallationFeedback;
