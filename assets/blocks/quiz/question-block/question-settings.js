/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { QuestionGradeSettings } from '../question-block/settings';

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
	...props
} ) => {
	const setOptions = ( next ) =>
		setAttributes( { options: { ...options, ...next } } );

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Question Settings', 'sensei-lms' ) }
				initialOpen={ true }
			>
				{ [ QuestionGradeSettings, ...controls ].map(
					( SettingControl ) => (
						<SettingControl
							key={ SettingControl }
							{ ...props }
							{ ...{ options, setOptions } }
						/>
					)
				) }
			</PanelBody>
		</InspectorControls>
	);
};

export default QuestionSettings;
