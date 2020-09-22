import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { ModuleStatusControl } from './module-status-control';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {number}   props.attributes.id
 * @param {Object}   props.backgroundColor
 * @param {Object}   props.textColor
 * @param {Function} props.setTextColor
 * @param {Function} props.setBackgroundColor
 */
export function ModuleBlockSettings( {
	isPreviewCompleted,
	setIsPreviewCompleted,
	animationsEnabled,
	setAnimationsEnabled,
} ) {
	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Status', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<ModuleStatusControl
					isPreviewCompleted={ isPreviewCompleted }
					setIsPreviewCompleted={ setIsPreviewCompleted }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Enable Animations', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					checked={ animationsEnabled }
					onChange={ setAnimationsEnabled }
					label={ __(
						'Enable animations on module collapse/expand.',
						'sensei-lms'
					) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}
