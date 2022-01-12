/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';

/**
 * Course progress component.
 *
 * @param {Object}  props
 * @param {number}  props.lessonsCount            Number of lessons.
 * @param {number}  props.completedCount          Number of completed lessons.
 * @param {boolean} props.hidePercentage          Hide completed percentage.
 * @param {Object}  props.wrapperAttributes       Wrapper HTML attributes.
 * @param {Object}  props.barWrapperAttributes    Bar wrapper HTML attributes.
 * @param {Object}  props.barAttributes           Bar HTML attributes.
 * @param {string}  props.countersClassName       Counters class name.
 * @param {string}  props.lessonsCountClassName   Lessons count class name.
 * @param {string}  props.completedCountClassName Completed count class name.
 * @param {boolean} props.hideDefault             Hide default settings for edit view only.
 */
const ProgressBar = ( {
	lessonsCount,
	completedCount,
	hidePercentage,
	wrapperAttributes,
	barWrapperAttributes,
	barAttributes,
	countersClassName,
	lessonsCountClassName,
	completedCountClassName,
	hideDefault,
} ) => {
	const completePercentage =
		Math.round( ( completedCount / lessonsCount ) * 100 ) || 0;
	const barPercentage = Math.max( hideDefault ? 0 : 3, completePercentage );

	return (
		<div { ...wrapperAttributes }>
			<section
				className={ classnames(
					'sensei-progress-bar__heading',
					countersClassName
				) }
			>
				<div
					className={ classnames(
						'sensei-progress-bar__lessons',
						lessonsCountClassName
					) }
				>
					{ sprintf(
						// translators: placeholder is number of lessons in the course.
						_n(
							'%d Lesson',
							'%d Lessons',
							lessonsCount,
							'sensei-lms'
						),
						lessonsCount
					) }
				</div>
				<div
					className={ classnames(
						'sensei-progress-bar__completed',
						completedCountClassName
					) }
				>
					{ sprintf(
						// translators: placeholder is number of completed lessons in the course.
						_n(
							'%d Completed',
							'%d Completed',
							completedCount,
							'sensei-lms'
						),
						completedCount
					) }
					{ ! hidePercentage && ` (${ completePercentage }%)` }
				</div>
			</section>
			<div
				role="progressbar"
				aria-valuenow={ completePercentage }
				aria-valuemin="0"
				aria-valuemax="100"
				{ ...{
					...barWrapperAttributes,
					className: classnames(
						'sensei-progress-bar__bar',
						barWrapperAttributes?.className
					),
				} }
			>
				<div
					{ ...{
						...barAttributes,
						style: {
							...( barAttributes?.style && barAttributes.style ),
							width: `${ barPercentage }%`,
						},
					} }
				/>
			</div>
		</div>
	);
};

export default ProgressBar;
