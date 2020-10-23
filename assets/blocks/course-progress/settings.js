import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The course progress block settings.
 *
 * @param {Object}   props                     Component properties.
 * @param {number}   props.manualPercentage    The value of the percentage.
 * @param {Function} props.setManualPercentage Callback to set the value of manual percentage.
 * @param {number}   props.borderRadius        The value of the bar radius.
 * @param {Function} props.setBorderRadius     Callback to set the value of border radius.
 * @param {number}   props.height              The value of the bar height.
 * @param {Function} props.setHeight           Callback to set the value of height.
 */
export function CourseProgressSettings( {
	manualPercentage,
	setManualPercentage,
	borderRadius,
	setBorderRadius,
	height,
	setHeight,
} ) {
	const initialHeight = 14;
	const initialBorderRadius = 2;

	borderRadius =
		undefined === borderRadius ? initialBorderRadius : borderRadius;
	height = undefined === height ? initialHeight : height;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Progress percentage', 'sensei-lms' ) }
				initialOpen={ false }
			>
				<RangeControl
					help={ __(
						'Preview the progress bar for different percentage values.',
						'sensei-lms'
					) }
					value={ manualPercentage }
					onChange={ setManualPercentage }
					min={ 0 }
					max={ 100 }
					allowReset={ true }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Progress bar settings', 'sensei-lms' ) }
				initialOpen={ false }
				className="wp-block-sensei-lms-progress-styling-controls"
			>
				<PanelRow>
					<RangeControl
						label={ 'Border radius' }
						help={ __(
							'Set the border radius of the progress bar.',
							'sensei-lms'
						) }
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
						help={ __(
							'Set the progress bar height.',
							'sensei-lms'
						) }
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
}
