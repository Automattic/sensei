import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, ExternalLink } from '@wordpress/components';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {number}   props.attributes.id
 * @param {Object}   props.backgroundColor
 * @param {Object}   props.textColor
 * @param {Function} props.setTextColor
 * @param {Function} props.setBackgroundColor
 */
export function LessonBlockSettings( {
	attributes: { id },
	backgroundColor,
	textColor,
	setTextColor,
	setBackgroundColor,
} ) {
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
