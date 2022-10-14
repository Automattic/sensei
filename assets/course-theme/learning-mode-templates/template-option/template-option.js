/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TemplateOptionFooter } from './template-option-footer';
import { TemplateOptionThumbnail } from './template-option-thumbnail';

/**
 * Renders the individual template.
 *
 * @param {Object}   props
 * @param {string}   props.name                  The name of the template.
 * @param {string}   props.title                 The title of the template.
 * @param {Object}   props.screenshots           The urls to screenshot images of the template.
 * @param {string}   props.screenshots.thumbnail The url to the thumbnail screenshot of the template.
 * @param {Function} props.onPreview             The callback that handles the preview.
 */
export const TemplateOption = ( props ) => {
	const { name, title, screenshots, onPreview } = props;

	const handlePreview = useCallback( () => onPreview( name ), [
		onPreview,
		name,
	] );

	return (
		<li className="sensei-lm-template-option__container">
			<TemplateOptionThumbnail
				title={ title }
				url={ screenshots.thumbnail }
				onPreview={ handlePreview }
			/>
			<TemplateOptionFooter { ...props } />
		</li>
	);
};
