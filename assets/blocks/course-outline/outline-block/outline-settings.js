/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useSharedModuleStyles } from './use-shared-module-styles';

/**
 * Inspector controls for outline block.
 *
 * @param {Object} props Block props.
 */
const OutlineSettings = ( props ) => {
	const { attributes, setAttributes } = props;

	const { moduleBorder, setModuleBorder } = useSharedModuleStyles( props );

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Modules', 'sensei-lms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					checked={ attributes.collapsibleModules }
					onChange={ ( value ) =>
						setAttributes( { collapsibleModules: value } )
					}
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
};

export default OutlineSettings;
