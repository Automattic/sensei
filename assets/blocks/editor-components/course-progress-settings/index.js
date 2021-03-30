/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The course progress settings.
 *
 * @param {Object}   props                 Component properties.
 * @param {number}   props.borderRadius    The value of the bar radius.
 * @param {Function} props.setBorderRadius Callback to set the value of border radius.
 * @param {number}   props.height          The value of the bar height.
 * @param {Function} props.setHeight       Callback to set the value of height.
 */
const CourseProgressSettings = ( {
	borderRadius,
	setBorderRadius,
	height,
	setHeight,
} ) => {
	const initialHeight = 14;
	const initialBorderRadius = 10;

	borderRadius =
		undefined === borderRadius ? initialBorderRadius : borderRadius;
	height = undefined === height ? initialHeight : height;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Progress bar settings', 'sensei-lms' ) }
				initialOpen={ false }
				className="sensei-course-progress-settings"
			>
				<PanelRow>
					<RangeControl
						label={ __( 'Border radius', 'sensei-lms' ) }
						value={ borderRadius }
						onChange={ setBorderRadius }
						min={ 0 }
						max={ 15 }
						allowReset={ true }
						initialPosition={ initialBorderRadius }
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __( 'Height', 'sensei-lms' ) }
						value={ height }
						onChange={ setHeight }
						min={ 1 }
						max={ 25 }
						allowReset={ true }
						initialPosition={ initialHeight }
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};

export default CourseProgressSettings;
