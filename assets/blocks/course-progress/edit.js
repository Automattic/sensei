import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';

import {
	withColorSettings,
	withDefaultColor,
} from '../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../course-outline/status-store';
import { CourseProgressSettings } from './settings';
import useToggleLegacyMetaboxes from '../use-toggle-legacy-metaboxes';

/**
 * Edit course progress bar component.
 *
 * @param {Object}   props                         Component properties.
 * @param {string}   props.className               Custom class name.
 * @param {Object}   props.barColor                Color object for the progress bar.
 * @param {Object}   props.defaultBarColor         Default bar color.
 * @param {Object}   props.barBackgroundColor      Color object for the background of the progress bar.
 * @param {Object}   props.textColor               Color object for the text.
 * @param {Object}   props.attributes              Component attributes.
 * @param {number}   props.attributes.height       The height of the progress bar.
 * @param {number}   props.attributes.borderRadius The border radius of the progress bar.
 * @param {boolean}  props.attributes.isPreview    Is preview flag.
 * @param {Function} props.setAttributes           Callback to set the component attributes.
 */
export const EditCourseProgressBlock = ( {
	className,
	barColor,
	defaultBarColor,
	barBackgroundColor,
	textColor,
	attributes: { height, borderRadius, isPreview },
	setAttributes,
} ) => {
	useToggleLegacyMetaboxes( { ignoreToggle: isPreview } );

	const { totalLessonsCount, completedLessonsCount } = useSelect(
		( select ) => select( COURSE_STATUS_STORE ).getLessonCounts(),
		[]
	);

	let progress = 0;
	if ( 0 !== totalLessonsCount ) {
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
		className: barColor?.class || defaultBarColor?.className,
		style: {
			backgroundColor: barColor?.color || defaultBarColor?.color,
			width: Math.max( 3, progress ) + '%',
			borderRadius,
		},
	};
	const barBackgroundAttributes = {
		className: classnames(
			'wp-block-sensei-lms-progress-bar',
			barBackgroundColor?.class
		),
		style: {
			backgroundColor: barBackgroundColor?.color,
			height,
			borderRadius,
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
			<CourseProgressSettings
				borderRadius={ borderRadius }
				setBorderRadius={ ( newRadius ) =>
					setAttributes( { borderRadius: newRadius } )
				}
				height={ height }
				setHeight={ ( newHeight ) =>
					setAttributes( { height: newHeight } )
				}
			/>
		</>
	);
};

export default compose(
	withColorSettings( {
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
	} ),
	withDefaultColor( {
		defaultBarColor: {
			style: 'background-color',
			probeKey: 'primaryColor',
		},
	} )
)( EditCourseProgressBlock );
