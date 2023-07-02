/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
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
				title={ __( 'Question settings', 'sensei-lms' ) }
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
				<ToggleControl
					label={ __( 'Hide Answer Feedback', 'sensei-lms' ) }
					checked={ options.hideAnswerFeedback === 'yes' }
					onChange={ ( value ) =>
						setOptions( { hideAnswerFeedback: value ? 'yes' : '' } )
					}
					help={ __(
						'Do not show any feedback when the student answers this question.',
						'sensei-lms'
					) }
				/>
			</PanelBody>
		</InspectorControls>
	);
};

export default QuestionSettings;
