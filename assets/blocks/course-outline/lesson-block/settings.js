import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { colorSetting } from '../../../shared/blocks/settings';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props
 * @param {Function} props.setAttributes
 * @param {Object}   props.style
 */
export function LessonBlockSettings( { style, setAttributes } ) {
	return (
		<InspectorControls>
			<PanelColorSettings
				title={ __( 'Color settings', 'sensei-lms' ) }
				colorSettings={ [
					colorSetting(
						'backgroundColor',
						__( 'Background color', 'sensei-lms' ),
						{ style, setAttributes }
					),
					colorSetting(
						'textColor',
						__( 'Text color', 'sensei-lms' ),
						{ style, setAttributes }
					),
				] }
			>
				{
					<ContrastChecker
						{ ...{
							textColor: style.textColor,
							backgroundColor: style.mainColor,
						} }
						isLargeText={ false }
					/>
				}
			</PanelColorSettings>
		</InspectorControls>
	);
}
