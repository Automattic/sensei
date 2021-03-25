/**
 * WordPress dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';

/**
 * Course progress component.
 *
 * @param {Object} props
 * @param {number} props.lessons   Number of lessons.
 * @param {number} props.completed Number of completed lessons.
 */
const CourseProgress = ( { lessons, completed } ) => {
	const completePercentage = ( completed / lessons ) * 100;

	return (
		<div className="wp-block-sensei-lms-learner-courses__course-progress">
			<div
				className="wp-block-sensei-lms-learner-courses__progress-bar"
				role="progressbar"
				aria-valuenow={ completePercentage }
				aria-valuemin="0"
				aria-valuemax="100"
			>
				<div
					className="wp-block-sensei-lms-learner-courses__progress-bar__fill"
					style={ { width: `${ completePercentage }%` } }
				></div>
			</div>
			<div className="wp-block-sensei-lms-learner-courses__course-progress__numbers">
				<strong className="wp-block-sensei-lms-learner-courses__course-progress__number-lessons">
					{ sprintf(
						// translators: placeholder is number of lessons in the course.
						_n( '%d Lesson', '%d Lessons', lessons, 'sensei-lms' ),
						lessons
					) }
				</strong>
				<em className="wp-block-sensei-lms-learner-courses__course-progress__completed-lessons">
					{ sprintf(
						// translators: placeholder is number of completed lessons in the course.
						_n(
							'%d Completed',
							'%d Completed',
							completed,
							'sensei-lms'
						),
						completed
					) }
				</em>
			</div>
		</div>
	);
};

export default CourseProgress;
