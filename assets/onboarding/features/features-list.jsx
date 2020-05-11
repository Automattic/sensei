import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';
import CheckIcon from './check-icon';

/**
 * Features list.
 *
 * @param {Object} props
 * @param {string} props.className Additional class name to the list.
 * @param {Object} props.children  React children.
 */
const FeaturesList = ( { className, children } ) => (
	<ul className={ className + ' sensei-onboarding__features-list' }>
		{ children }
	</ul>
);

/**
 * Features list item.
 *
 * @param {Object} props
 * @param {string} props.title                          Feature title.
 * @param {string} props.description                    Feature description.
 * @param {string} [props.confirmationExtraDescription] Extra description that appears only in confirmation modal.
 * @param {string} [props.learnMoreLink]                Learn more link.
 * @param {string} [props.errorMessage]                 Error message.
 * @param {string} [props.onFeatureRetry]               Retry feature installation callback.
 * @param {string} [props.status]                       Feature status.
 */
FeaturesList.Item = ( {
	title,
	description,
	confirmationExtraDescription,
	learnMoreLink,
	errorMessage,
	onFeatureRetry,
	status,
} ) => (
	<li className="sensei-onboarding__features-list-item">
		{ status && (
			<div className="sensei-onboarding__icon-status">
				{ status === FeaturesList.LOADING_STATUS && (
					<>
						<Spinner />
						<span className="screen-reader-text">
							{ __( 'Loading', 'sensei-lms' ) }
						</span>
					</>
				) }
				{ status === FeaturesList.ERROR_STATUS && (
					<i className="sensei-onboarding__circle-icon-wrapper error-icon-wrapper alert-icon">
						{ __( 'Error', 'sensei-lms' ) }
					</i>
				) }
				{ status === FeaturesList.SUCCESS_STATUS && (
					<i className="sensei-onboarding__circle-icon-wrapper success-icon-wrapper">
						<CheckIcon />
						{ __( 'Success', 'sensei-lms' ) }
					</i>
				) }
			</div>
		) }

		<div>
			<h4 className="sensei-onboarding__feature-title">{ title }</h4>
			<p className="sensei-onboarding__feature-description">
				<FeatureDescription
					description={ description }
					confirmationExtraDescription={
						confirmationExtraDescription
					}
					learnMoreLink={ learnMoreLink }
				/>
			</p>
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
		</div>
	</li>
);
FeaturesList.Item.displayName = 'FeaturesList.Item';

/**
 * Status constants.
 */
FeaturesList.LOADING_STATUS = 'loading';
FeaturesList.ERROR_STATUS = 'error';
FeaturesList.SUCCESS_STATUS = 'success';

export default FeaturesList;
