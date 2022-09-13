/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Renders the Learning Mode template thumbnail.
 *
 * @param {Object}   props
 * @param {string}   props.title     The title of the template.
 * @param {string}   props.url       The url of the image.
 * @param {Function} props.onPreview The callback to show preview.
 */
export const TemplateOptionThumbnail = ( { title, url, onPreview } ) => {
	return (
		<div
			className="sensei-lm-template-option__thumbnail"
			onKeyPress={ onPreview }
			onClick={ onPreview }
			tabIndex="0"
			role="option"
			title={ title }
		>
			<img alt={ title } src={ url } />
			<div className="sensei-lm-template-option__preview">
				<h4 className="sensei-lm-template-option__preview-title">
					{ __( 'Preview', 'sensei-lms' ) }
				</h4>
			</div>
		</div>
	);
};
