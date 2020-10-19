import { withColorSettings } from '../../shared/blocks/settings';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { COURSE_STATUS_STORE } from '../course-outline/status-store';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

/**
 * Edit course progress bar component.
 *
 * @param {Object} props                    Component properties.
 * @param {string} props.className          Custom class name.
 * @param {Object} props.barColor           Color object for the progress bar.
 * @param {Object} props.barBackgroundColor Color object for the background of the progress bar.
 * @param {Object} props.textColor          Color object for the text.
 */
export const EditCourseProgressBlock = ( {
	className,
	barColor,
	barBackgroundColor,
	textColor,
} ) => {
	const { totalLessonsCount, completedLessonsCount } = useSelect(
		( select ) => select( COURSE_STATUS_STORE ).getLessonCounts(),
		[]
	);

	const [ manualPercentage, setManualPercentage ] = useState( null );

	let progress = 0;

	if ( null !== manualPercentage ) {
		progress = manualPercentage;
	} else if ( 0 !== totalLessonsCount ) {
		progress =
			Math.round(
				( ( 100 * completedLessonsCount ) / totalLessonsCount +
					Number.EPSILON ) *
					100
			) / 100;
	}

	const wrapperAttributes = {
		className: classnames( className, textColor?.class ),
		style: {
			color: textColor?.color,
		},
	};
	const barAttributes = {
		className: barColor?.class,
		style: {
			backgroundColor: barColor?.color,
			width: progress + '%',
		},
	};
	const barBackgroundAttributes = {
		className: classnames(
			'wp-block-sensei-lms-progress-bar',
			barBackgroundColor?.class
		),
		style: {
			backgroundColor: barBackgroundColor?.color,
		},
	};

	return (
		<>
			<div { ...wrapperAttributes }>
				<section className="wp-block-sensei-lms-progress-heading">
					<div className="wp-block-sensei-lms-progress-heading__lessons">
						{ totalLessonsCount } Lessons
					</div>
					<div className="wp-block-sensei-lms-progress-heading__completed">
						{ completedLessonsCount } completed ({ progress }%)
					</div>
				</section>
				<div
					role="progressbar"
					aria-valuenow={ progress }
					aria-valuemin="0"
					aria-valuemax="100"
					{ ...barBackgroundAttributes }
				>
					<div { ...barAttributes } />
				</div>
			</div>

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
						value={ manualPercentage ?? 0 }
						onChange={ setManualPercentage }
						min={ 0 }
						max={ 100 }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export default withColorSettings( {
	barColor: {
		style: 'background-color',
		label: __( 'Progress bar color', 'sensei-lms' ),
	},
	barBackgroundColor: {
		style: 'background-color',
		label: __( 'Progress bar background color', 'sensei-lms' ),
	},
	textColor: {
		style: 'color',
		label: __( 'Text color', 'sensei-lms' ),
	},
} )( EditCourseProgressBlock );
