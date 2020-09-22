import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { PanelBody, FontSizePicker, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props                     Component props.
 * @param {Object}   props.backgroundColor     The lesson title background color.
 * @param {Object}   props.textColor           The lesson title color.
 * @param {Function} props.setTextColor        Callback method to set the lesson title color.
 * @param {Function} props.setBackgroundColor  Callback method to set the background color.
 * @param {Function} props.setAttributes       Callback method to set the lesson title font size.
 * @param {Function} props.attributes          The block attributes.
 * @param {number}   props.attributes.id       The lesson id.
 * @param {Function} props.attributes.fontSize The lesson block font size.
 */
export function LessonBlockSettings( {
	attributes: { id },
	backgroundColor,
	textColor,
	setTextColor,
	setBackgroundColor,
	setAttributes,
	attributes: { fontSize },
} ) {
	const { fontSizes } = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSettings()
	);

	return (
		<InspectorControls>
			{ id && (
				<PanelBody>
					<h2>
						<ExternalLink
							href={ `post.php?post=${ id }&action=edit` }
							target="lesson"
							className="wp-block-sensei-lms-course-outline-lesson__edit"
						>
							{ __( 'Edit lesson', 'sensei-lms' ) }
						</ExternalLink>
					</h2>
					<p>
						{ __(
							'Edit details such as lesson content, prerequisite, quiz settings and more.',
							'sensei-lms'
						) }
					</p>
				</PanelBody>
			) }
			<PanelBody
				title={ __( 'Typography', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<FontSizePicker
					fontSizes={ fontSizes }
					value={ fontSize }
					onChange={ ( value ) => {
						setAttributes( { fontSize: value } );
					} }
				/>
			</PanelBody>
			<PanelColorSettings
				title={ __( 'Color settings', 'sensei-lms' ) }
				colorSettings={ [
					{
						value: backgroundColor.color,
						label: __( 'Background color', 'sensei-lms' ),
						onChange: setBackgroundColor,
					},
					{
						value: textColor.color,
						label: __( 'Text color', 'sensei-lms' ),
						onChange: setTextColor,
					},
				] }
			>
				{
					<ContrastChecker
						{ ...{
							textColor: textColor.color,
							backgroundColor: backgroundColor.color,
						} }
						isLargeText={ false }
					/>
				}
			</PanelColorSettings>
		</InspectorControls>
	);
}
