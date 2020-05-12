import { List } from '@woocommerce/components';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * @typedef  {Object} Feature
 * @property {string} title                          Feature title.
 * @property {string} description                    Feature description.
 * @property {string} [confirmationExtraDescription] Extra description that appears only in confirmation modal.
 */
/**
 * Features confirmation modal.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features  Features list.
 * @param {Function}  props.onInstall Callback to install the features.
 * @param {Function}  props.onSkip    Callback to skip the installation.
 */
const ConfirmationModal = ( { features = [], onInstall, onSkip } ) => (
	<Modal
		className="sensei-onboarding__features-confirmation-modal"
		title={ __(
			'Would you like to install the following features now?',
			'sensei-lms'
		) }
		isDismissible={ false }
	>
		<List
			items={ features.map(
				( { title, description, confirmationExtraDescription } ) => ( {
					title,
					content: `${ description } ${ confirmationExtraDescription }`,
				} )
			) }
		/>

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
				onClick={ onSkip }
			>
				{ __( "I'll do it later", 'sensei-lms' ) }
			</Button>
			<Button
				className="sensei-onboarding__button"
				isPrimary
				onClick={ onInstall }
			>
				{ __( 'Install now', 'sensei-lms' ) }
			</Button>
		</div>
	</Modal>
);

export default ConfirmationModal;
