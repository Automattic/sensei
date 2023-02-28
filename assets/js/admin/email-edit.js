/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const EmailPreviewLink = () => {
	useEffect( () => {
		const container = document.querySelector(
			'.block-editor-post-preview__dropdown'
		);

		if ( ! container ) {
			return;
		}

		render(
			<a
				href={ sensei_email_preview.link }
				className="components-button is-tertiary"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'Preview', 'sensei-lms' ) }
			</a>,
			container
		);
	} );

	return null;
};

registerPlugin( 'sensei-email-preview-plugin', {
	render: EmailPreviewLink,
} );
