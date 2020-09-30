import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ModuleStatusControl } from './module-status-control';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 */
export const ModuleBlockSettings = ( {
	isPreviewCompleted,
	setIsPreviewCompleted,
} ) => (
	<InspectorControls>
		<PanelBody title={ __( 'Status', 'sensei-lms' ) } initialOpen={ false }>
			<ModuleStatusControl
				isPreviewCompleted={ isPreviewCompleted }
				setIsPreviewCompleted={ setIsPreviewCompleted }
			/>
		</PanelBody>
	</InspectorControls>
);
