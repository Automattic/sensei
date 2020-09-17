import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props
 * @param {Object}   props.backgroundColor
 * @param {Object}   props.textColor
 * @param {Function} props.setTextColor
 * @param {Function} props.setBackgroundColor
 */
export function LessonBlockSettings( {
	backgroundColor,
	textColor,
	setTextColor,
	setBackgroundColor,
} ) {
	return (
		<InspectorControls>
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
