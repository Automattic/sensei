import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for lesson actions block.
 *
 * @param {Object}   props
 * @param {Object}   props.toggledBlocks Toggled blocks, where the key is the block name.
 * @param {Function} props.toggleBlock   Toggle block.
 */
export const LessonActionsBlockSettings = ( {
	toggledBlocks,
	toggleBlock,
} ) => (
	<InspectorControls>
		<PanelBody title={ __( 'Additional Actions', 'sensei-lms' ) }>
			<ToggleControl
				checked={
					false !== toggledBlocks[ 'sensei-lms/button-reset-lesson' ]
				}
				onChange={ toggleBlock( 'sensei-lms/button-reset-lesson' ) }
				label={ __( 'Reset lesson', 'sensei-lms' ) }
			/>
		</PanelBody>
	</InspectorControls>
);
