/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Course progress component.
 *
 * @param {Object}  props
 * @param {number}  props.totalCount           Total count for progress bar.
 * @param {number}  props.completedCount       Number of completed.
 * @param {boolean} props.hidePercentage       Hide completed percentage.
 * @param {Object}  props.wrapperAttributes    Wrapper HTML attributes.
 * @param {Object}  props.barWrapperAttributes Bar wrapper HTML attributes.
 * @param {string}  props.countersClassName    Counters class name.
 * @param {Object}  props.barAttributes        Bar HTML attributes.
 * @param {boolean} props.hideDefault          Hide default settings for edit view only.
 * @param {boolean} props.progressBarLabel     Progress bar label.
 */
const ProgressBar = ( {
	totalCount,
	completedCount,
	hidePercentage,
	wrapperAttributes,
	barWrapperAttributes,
	barAttributes,
	countersClassName,
	hideDefault,
	progressBarLabel,
} ) => {
	const completePercentage =
		Math.round( ( completedCount / totalCount ) * 100 ) || 0;
	const barPercentage = Math.max( hideDefault ? 0 : 3, completePercentage );
	return (
		<div { ...wrapperAttributes }>
			<section
				className={ classnames(
					'sensei-progress-bar__heading',
					'wp-block-sensei-lms-progress-heading',
					countersClassName
				) }
			>
				<div
					className={ classnames(
						'sensei-progress-bar__completed',
						'wp-block-sensei-lms-progress-heading__completed'
					) }
				>
					{ sprintf(
						// translators: Placeholder %1$d is the completed progress count, %2$d is the total count and %3$s is the label for progress bar.
						__( '%1$d of %2$d %3$s complete ', 'sensei-lms' ),
						completedCount,
						totalCount,
						progressBarLabel ? progressBarLabel : ''
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
						'wp-block-sensei-lms-course-progress',
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
