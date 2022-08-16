/**
 * WordPress dependencies
 */
import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const Settings = ( props ) => (
	<InspectorControls>
		<PanelColorSettings
			title={ __( 'Category Colors', 'sensei-lms' ) }
			initialOpen={ true }
			colorSettings={ props.colorSettings }
		>
			<ContrastChecker
				{ ...{
					textColor: props.textColor?.color,
					backgroundColor: props.backgroundColor?.color,
				} }
				isLargeText={ false }
			/>
		</PanelColorSettings>
	</InspectorControls>
);
