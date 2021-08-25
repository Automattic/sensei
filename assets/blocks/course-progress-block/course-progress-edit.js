/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	withColorSettings,
	withDefaultColor,
} from '../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../course-outline/status-preview/status-store';
import CourseProgress, {
	CourseProgressSettings,
} from '../../shared/blocks/course-progress';

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
 * @param {Function} props.setAttributes           Callback to set the component attributes.
 */
export const CourseProgressEdit = ( props ) => {
	const {
		className,
		barColor,
		defaultBarColor,
		barBackgroundColor,
		textColor,
		attributes: { height, borderRadius },
		setAttributes,
	} = props;

	const { totalLessonsCount, completedLessonsCount } = useSelect(
		( select ) => select( COURSE_STATUS_STORE ).getLessonCounts(),
		[]
	);

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
			borderRadius,
		},
	};
	const barWrapperAttributes = {
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
			<CourseProgress
				lessonsCount={ totalLessonsCount }
				completedCount={ completedLessonsCount }
				wrapperAttributes={ wrapperAttributes }
				barWrapperAttributes={ barWrapperAttributes }
				barAttributes={ barAttributes }
				countersClassName="wp-block-sensei-lms-progress-heading"
				lessonsCountClassName="wp-block-sensei-lms-progress-heading__lessons"
				completedCountClassName="wp-block-sensei-lms-progress-heading__completed"
			/>
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
)( CourseProgressEdit );
