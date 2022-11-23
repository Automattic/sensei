/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ToolbarDropdown from '../../editor-components/toolbar-dropdown';
import { COMPLETED_PREVIEW, IN_PROGRESS_PREVIEW } from './constants';

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
const LessonActionsSettings = ( {
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
							label: __( 'In Progress', 'sensei-lms' ),
							value: IN_PROGRESS_PREVIEW,
						},
						{
							label: __( 'Completed', 'sensei-lms' ),
							value: COMPLETED_PREVIEW,
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

export default LessonActionsSettings;
