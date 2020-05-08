import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeaturesList from './features-list';

/**
 * @typedef  {Object} Feature
 * @property {string} id                           Feature ID.
 * @property {string} title                        Feature title.
 * @property {string} description                  Feature description.
 * @property {string} confirmationExtraDescription Extra description that appears only in confirmation modal.
 */
/**
 * Modal for usage tracking opt-in.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features Features list.
 * @param {Function}  props.install  Callback to install the features.
 * @param {Function}  props.skip     Callback to skip the installation.
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
		<FeaturesList>
			{ features.map(
				( {
					id,
					title,
					description,
					confirmationExtraDescription,
				} ) => (
					<FeaturesList.Item
						key={ id }
						title={ title }
						description={ description }
						confirmationExtraDescription={
							confirmationExtraDescription
						}
					/>
				)
			) }
		</FeaturesList>

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
