import { Button, Modal, CheckboxControl } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import interpolateComponents from 'interpolate-components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Modal for usage tracking opt-in.
 *
 * @param {Object}   props
 * @param {boolean}  props.tracking     Initial tracking state.
 * @param {Function} props.onContinue   Callback for user pressing the continue button.
 * @param {Function} props.onClose      Callback for closing the modal.
 * @param {boolean}  props.isSubmitting Indicate loading state.
 * @param {string}   props.children     Children elements of the modal.
 * @class
 */
export const UsageModal = ( {
	tracking,
	onContinue,
	onClose,
	isSubmitting,
	children,
} ) => {
	const trackingMessage = interpolateComponents( {
		mixedString: __(
			'Get improved features and faster fixes by sharing non-sensitive data via {{link}}usage tracking{{/link}} ' +
				'that shows us how Sensei LMS is used. No personal data is tracked or stored.',
			'sensei-lms'
		),
		components: {
			link: (
				<Link
					href="https://senseilms.com/documentation/what-data-does-sensei-track/"
					className="link__color-secondary"
					target="_blank"
					type="external"
				/>
			),
		},
	} );

	const [ allowTracking, setAllowTracking ] = useState( false );
	useEffect( () => setAllowTracking( tracking ), [ tracking ] );

	return (
		<Modal
			title={ __( 'Build a Better Sensei LMS', 'sensei-lms' ) }
			onRequestClose={ onClose }
			className="sensei-setup-wizard__usage-modal"
		>
			<div className="sensei-setup-wizard__usage-wrapper">
				<div className="sensei-setup-wizard__usage-modal-message">
					{ trackingMessage }
				</div>
				<div className="sensei-setup-wizard__tracking">
					<CheckboxControl
						className="sensei-setup-wizard__tracking-checkbox"
						checked={ allowTracking }
						label={ __( 'Yes, count me in!', 'sensei-lms' ) }
						onChange={ () => setAllowTracking( ! allowTracking ) }
					/>
				</div>
				{ children }
				<Button
					className="sensei-setup-wizard__button sensei-setup-wizard__button-modal"
					isPrimary
					isBusy={ isSubmitting }
					disabled={ isSubmitting }
					onClick={ () => onContinue( allowTracking ) }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</div>
		</Modal>
	);
};
