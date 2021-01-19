import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import ToolbarDropdown from '../../editor-components/toolbar-dropdown';

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
 * @param {ToggleBlock[]} props.toggleBlocks    Blocks to toggle.
 * @param {string}        props.previewState    Preview state.
 * @param {Function}      props.onPreviewChange Preview change callback.
 */
export const LessonActionsBlockSettings = ( {
	toggleBlocks,
	previewState,
	onPreviewChange,
} ) => (
	<>
		<BlockControls>
			<ToolbarGroup>
				<ToolbarDropdown
					options={ [
						{
							label: __( 'In progress', 'sensei-lms' ),
							value: 'in-progress',
						},
						{
							label: __( 'Completed', 'sensei-lms' ),
							value: 'completed',
						},
					] }
					optionsLabel={ __( 'Preview lesson state', 'sensei-lms' ) }
					value={ previewState }
					onChange={ onPreviewChange }
				/>
			</ToolbarGroup>
		</BlockControls>
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
	</>
);
