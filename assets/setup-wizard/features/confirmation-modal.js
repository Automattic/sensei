import { List } from '@woocommerce/components';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FeatureDescription from './feature-description';

/**
 * @typedef  {Object} Feature
 * @property {string} slug    Feature slug.
 * @property {string} title   Feature title.
 * @property {string} excerpt Feature excerpt.
 */
/**
 * Features confirmation modal.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features      Features list.
 * @param {boolean}   props.isSubmitting  Is submitting state.
 * @param {Element}   [props.errorNotice] Submit error notice.
 * @param {Function}  props.onInstall     Callback to install the features.
 * @param {Function}  props.onSkip        Callback to skip the installation.
 */
const ConfirmationModal = ( {
	features = [],
	isSubmitting,
	errorNotice,
	onInstall,
	onSkip,
} ) => (
	<Modal
		className="sensei-setup-wizard__features-confirmation-modal"
		title={ __(
			'Would you like to install the following features now?',
			'sensei-lms'
		) }
		isDismissible={ false }
	>
		<List
			items={ features.map( ( { slug, title, excerpt } ) => ( {
				title,
				content: (
					<FeatureDescription
						slug={ slug }
						excerpt={ excerpt }
						selectedFeatures={ features }
					/>
				),
			} ) ) }
		/>

		<p>
			{ __(
				"You won't have access to this functionality until the extensions have been installed.",
				'sensei-lms'
			) }
		</p>

		{ errorNotice }

		<div className="sensei-setup-wizard__group-buttons group-right">
			<Button
				className="sensei-setup-wizard__button"
				isTertiary
				isBusy={ isSubmitting }
				disabled={ isSubmitting }
				onClick={ onSkip }
			>
				{ __( "I'll do it later", 'sensei-lms' ) }
			</Button>
			<Button
				className="sensei-setup-wizard__button"
				isPrimary
				isBusy={ isSubmitting }
				disabled={ isSubmitting }
				onClick={ onInstall }
			>
				{ __( 'Install now', 'sensei-lms' ) }
			</Button>
		</div>
	</Modal>
);

export default ConfirmationModal;
