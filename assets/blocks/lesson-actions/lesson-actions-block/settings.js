import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for lesson actions block.
 *
 * @param {Object}   props
 * @param {Object}   props.activeBlocks Active blocks, where the key is the block name.
 * @param {Function} props.toggleBlock  Toggle block.
 */
export const LessonActionsBlockSettings = ( { activeBlocks, toggleBlock } ) => (
	<InspectorControls>
		<PanelBody title={ __( 'Additional Actions', 'sensei-lms' ) }>
			<ToggleControl
				checked={
					false !== activeBlocks[ 'sensei-lms/button-reset-lesson' ]
				}
				onChange={ toggleBlock( 'sensei-lms/button-reset-lesson' ) }
				label={ __( 'Reset lesson', 'sensei-lms' ) }
			/>
		</PanelBody>
	</InspectorControls>
);
