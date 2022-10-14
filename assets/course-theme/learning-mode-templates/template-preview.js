/**
 * WordPress dependencies
 */
import { Modal, Button } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TemplateActions } from './template-actions';

/**
 * Renders the template preview.
 *
 * @param {Object}   props
 * @param {Function} props.onClose          Handles the modal closing.
 * @param {string}   props.title            The title of the template.
 * @param {Object}   props.screenshots      The url of the screenshots.
 * @param {string}   props.screenshots.full The url of the full size screenshot.
 */
export const TemplatePreview = ( props ) => {
	const { onClose, title, screenshots } = props;
	return (
		<Modal
			onRequestClose={ onClose }
			// translators: The %1$s is the name of the Learning Mode template.
			title={ sprintf( __( 'Preview %1$s', 'sensei-lms' ), title ) }
			className="sensei-lm-template-preview__modal"
		>
			<div className="sensei-lm-template-preview__container">
				<div className="sensei-lm-template-preview__img">
					<img alt={ title } src={ screenshots.full } />
				</div>

				<div className="sensei-lm-template-preview__footer">
					<Button
						className="sensei-lm-template-preview__cancel-btn"
						onClick={ onClose }
						variant="tertiary"
					>
						{ __( 'Cancel', 'sensei-lms' ) }
					</Button>
					<TemplateActions { ...props } />
				</div>
			</div>
		</Modal>
	);
};
