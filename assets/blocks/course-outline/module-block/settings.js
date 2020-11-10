import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ShareStyle } from '../../../shared/blocks/share-style';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 */
export const ModuleBlockSettings = ( props ) => {
	return (
		<>
			<InspectorAdvancedControls>
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
			</InspectorAdvancedControls>
		</>
	);
};
