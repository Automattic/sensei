import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ButtonGroup, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.bordered    Whether the border is enabled
 * @param {Function} props.setBordered Callback to enable the border.
 */
export function ModuleBlockSettings( { bordered, setBordered } ) {
	const createButton = ( text, selectedValue ) => {
		return (
			<Button
				isDefault={ selectedValue !== bordered }
				isPrimary={ selectedValue === bordered }
				onClick={ () => setBordered( selectedValue ) }
			>
				{ text }
			</Button>
		);
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Border settings', 'sensei-lms' ) }
				initialOpen={ false }
			>
				<ButtonGroup
					id="wp-block-sensei-lms-module-button-group"
					aria-describedby="wp-block-sensei-lms-module-button-group-label"
				>
					{ createButton( __( 'Default', 'sensei-lms' ), 'parent' ) }
					{ createButton( __( 'Enabled', 'sensei-lms' ), 'on' ) }
					{ createButton( __( 'Disabled', 'sensei-lms' ), 'off' ) }
				</ButtonGroup>
				<label
					id="wp-block-sensei-lms-module-button-group-label"
					className="wp-block-sensei-lms-module-button-group__label"
					htmlFor="wp-block-sensei-lms-module-button-group"
				>
					{ __(
						"Toggle the border of the module. Select 'Default' to inherit the setting of the Outline Block.",
						'sensei-lms'
					) }
				</label>
			</PanelBody>
		</InspectorControls>
	);
}
