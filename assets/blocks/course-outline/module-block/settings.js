import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ShareStyle } from '../../../shared/blocks/share-style';
import { ModuleStatusControl } from './module-status-control';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 * @param {Object}   props.colorSettingsFill     Slot for color settings panel.
 */
export const ModuleBlockSettings = ( props ) => {
	const {
		isPreviewCompleted,
		setIsPreviewCompleted,
		colorSettingsFill: ColorSettingsFill,
	} = props;

	return (
		<>
			<ColorSettingsFill>
				<ShareStyle
					{ ...props }
					sharedAttributeNames={ [
						'mainColor',
						'customMainColor',
						'textColor',
						'customTextColor',
						'className',
					] }
					label={ __( 'Apply style to all modules', 'sensei-lms' ) }
					help={ __(
						'Use the selected block style and color settings for all modules.',
						'sensei-lms'
					) }
				/>
			</ColorSettingsFill>
			<InspectorControls>
				<PanelBody
					title={ __( 'Status', 'sensei-lms' ) }
					initialOpen={ false }
				>
					<ModuleStatusControl
						isPreviewCompleted={ isPreviewCompleted }
						setIsPreviewCompleted={ setIsPreviewCompleted }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
