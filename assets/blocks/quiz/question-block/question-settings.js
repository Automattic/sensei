import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { QuestionGradeSettings } from './settings';

/**
 * Question block settings controls.
 *
 * @param {Object}     props                    Block props.
 * @param {Function[]} props.controls           Additional setting controls.
 * @param {Object}     props.attributes         Block attributes.
 * @param {Object}     props.attributes.options Block options attribute.
 * @param {Function}   props.setAttributes      Update block attributes.
 */
const QuestionSettings = ( {
	controls = [],
	attributes: { options = {} },
	setAttributes,
} ) => {
	const setOptions = ( next ) =>
		setAttributes( { options: { ...options, ...next } } );

	controls = [ QuestionGradeSettings, ...controls ];

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Question Settings', 'sensei-lms' ) }
				initialOpen={ true }
			>
				{ controls.map( ( SettingControl ) => (
					<SettingControl
						key={ SettingControl }
						{ ...{ options, setOptions } }
					/>
				) ) }
			</PanelBody>
			<PanelBody
				title={ __( 'Question Categories', 'sensei-lms' ) }
				initialOpen={ false }
			></PanelBody>
		</InspectorControls>
	);
};

export default QuestionSettings;
