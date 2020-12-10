import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Inspector controls for outline block.
 *
 * @param {Object}   props
 * @param {boolean}  props.collapsibleModules    Whether collapsible modules are enabled.
 * @param {Function} props.setCollapsibleModules Callback to be called when collapsible modules setting is updated.
 * @param {boolean}  props.moduleBorder          Whether modules borders are enabled.
 * @param {Function} props.setModuleBorder       Callback to set module borders.
 */
export const OutlineBlockSettings = ( {
	collapsibleModules,
	setCollapsibleModules,
	moduleBorder,
	setModuleBorder,
} ) => (
	<InspectorControls>
		<PanelBody
			title={ __( 'Modules', 'sensei-lms' ) }
			initialOpen={ false }
		>
			<ToggleControl
				checked={ collapsibleModules }
				onChange={ setCollapsibleModules }
				label={ __( 'Collapsible modules', 'sensei-lms' ) }
				help={ __(
					'Modules can be collapsed or expanded.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				checked={ moduleBorder }
				onChange={ setModuleBorder }
				label={ __( 'Border', 'sensei-lms' ) }
				help={ __(
					'Toggle the border for all modules.',
					'sensei-lms'
				) }
			/>
		</PanelBody>
	</InspectorControls>
);
