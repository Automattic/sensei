import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * @typedef {Object} ToggleBlock
 *
 * @property {string}   label    Toggle label.
 * @property {boolean}  active   Whether block is active.
 * @property {Function} onToggle Toggle function.
 */
/**
 * Inspector controls for lesson actions block.
 *
 * @param {Object}        props
 * @param {ToggleBlock[]} props.toggleBlocks Blocks to toggle.
 */
export const LessonActionsBlockSettings = ( { toggleBlocks } ) => (
	<InspectorControls>
		<PanelBody title={ __( 'Additional Actions', 'sensei-lms' ) }>
			{ toggleBlocks.map( ( block ) => (
				<ToggleControl
					key={ block.label }
					checked={ block.active }
					onChange={ block.onToggle }
					label={ block.label }
				/>
			) ) }
		</PanelBody>
	</InspectorControls>
);
