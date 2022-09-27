/**
 * WordPress dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { templates, activeTemplateName } from './data';
import { TemplateOption } from './template-option/template-option';
import { TemplatePreview } from './template-preview';

export const TemplateSelector = () => {
	const [ previewTemplateName, setPreviewTemplate ] = useState( null );
	const previewTemplate = templates[ previewTemplateName ] || {};

	const showPreview = useCallback(
		( name ) => setPreviewTemplate( name ),
		[]
	);

	const hidePreview = useCallback( () => setPreviewTemplate( null ), [] );

	return (
		<>
			{ Object.keys( templates ).map( ( templateName ) => {
				const templateData = templates[ templateName ];
				return (
					<TemplateOption
						{ ...templateData }
						key={ templateData.name }
						isActive={ activeTemplateName === templateData.name }
						onPreview={ showPreview }
					/>
				);
			} ) }
			{ previewTemplateName && (
				<TemplatePreview
					{ ...previewTemplate }
					onClose={ hidePreview }
					isActive={ activeTemplateName === previewTemplate.name }
				/>
			) }
		</>
	);
};
