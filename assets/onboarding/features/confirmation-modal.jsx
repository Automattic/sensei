import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';

/**
 * Modal for usage tracking opt-in.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features Features list.
 * @param {Function} props.install   Callback to install the features.
 * @param {Function} props.skip      Callback to skip the installation.
 *
 * @typedef  {Object} Feature
 * @property {string} id                           Feature ID.
 * @property {string} title                        Feature title.
 * @property {string} description                  Feature description.
 * @property {string} confirmationExtraDescription Extra description that appears only in confirmation modal.
 */
const ConfirmationModal = ( { features = [], install, skip } ) => (
	<Modal
		title={ __(
			'Would you like to install the following features now?',
			'sensei-lms'
		) }
		isDismissible={ false }
		className="sensei-onboarding__features-confirmation-modal"
	>
		<ul className="sensei-onboarding__features-list">
			{ features.map(
				( {
					id,
					title,
					description,
					confirmationExtraDescription,
				} ) => (
					<li
						key={ id }
						className="sensei-onboarding__features-list-item"
					>
						<h4 className="sensei-onboarding__feature-title">
							{ title }
						</h4>
						<p className="sensei-onboarding__feature-description">
							<FeatureDescription
								description={ description }
								confirmationExtraDescription={
									confirmationExtraDescription
								}
							/>
						</p>
					</li>
				)
			) }
		</ul>

		<p>
			{ __(
				"You won't have access to this funcitonality until the extensions have been installed.",
				'sensei-lms'
			) }
		</p>

		<div className="sensei-onboarding__group-buttons group-right">
			<Button
				className="sensei-onboarding__button"
				isTertiary
				onClick={ skip }
			>
				{ __( "I'll do it later", 'sensei-lms' ) }
			</Button>
			<Button
				className="sensei-onboarding__button"
				isPrimary
				onClick={ install }
			>
				{ __( 'Install now', 'sensei-lms' ) }
			</Button>
		</div>
	</Modal>
);

export default ConfirmationModal;
