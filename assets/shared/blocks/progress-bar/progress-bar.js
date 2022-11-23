/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Progress bar component.
 *
 * @param {Object}  props
 * @param {number}  props.totalCount              Total count for progress bar.
 * @param {number}  props.completedCount          Number of completed.
 * @param {boolean} props.hidePercentage          Hide completed percentage.
 * @param {Object}  props.wrapperAttributes       Wrapper HTML attributes.
 * @param {Object}  props.barWrapperAttributes    Bar wrapper HTML attributes.
 * @param {string}  props.countersClassName       Counters class name.
 * @param {string}  props.completedCountClassName Completed count class name.
 * @param {Object}  props.barAttributes           Bar HTML attributes.
 * @param {boolean} props.hideDefault             Hide default settings for edit view only.
 * @param {boolean} props.label                   Label.
 */
const ProgressBar = ( {
	totalCount,
	completedCount,
	hidePercentage,
	wrapperAttributes,
	barWrapperAttributes,
	barAttributes,
	countersClassName,
	completedCountClassName,
	hideDefault,
	label,
} ) => {
	const completePercentage =
		Math.round( ( completedCount / totalCount ) * 100 ) || 0;
	const barPercentage = Math.max( hideDefault ? 0 : 3, completePercentage );
	const blockProps = useBlockProps( wrapperAttributes );

	return (
		<div { ...blockProps }>
			<section
				className={ classnames(
					'sensei-progress-bar__heading',
					countersClassName
				) }
			>
				<div
					className={ classnames(
						'sensei-progress-bar__label',
						completedCountClassName
					) }
				>
					{ sprintf(
						// translators: Placeholder %1$d is the completed progress count, %2$d is the total count and %3$s is the label for progress bar.
						__( '%1$d of %2$d %3$s completed', 'sensei-lms' ),
						completedCount,
						totalCount,
						label ? label : ''
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
						className: classnames(
							'sensei-progress-bar__progress',
							barAttributes?.className
						),
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
