/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Inspector controls for course results block.
 *
 * @param {Object} props Block props.
 */
const CourseResultsSettings = ( props ) => {
	const {
		attributes: { moduleBorder },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Modules', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					checked={ moduleBorder }
					onChange={ ( newValue ) =>
						setAttributes( { moduleBorder: newValue } )
					}
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

export default CourseResultsSettings;
