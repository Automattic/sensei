/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Progress component.
 *
 * @param {Object} props                Component props.
 * @param {number} props.totalTasks     Number of tasks.
 * @param {number} props.completedTasks Number of completed tasks.
 */
const Progress = ( { totalTasks, completedTasks } ) => {
	const percentage = Math.round( ( completedTasks / totalTasks ) * 100 );

	return (
		<div className="sensei-home-task-progress">
			<div className="sensei-home-task-progress__number">
				{ percentage }%
			</div>

			<div className="sensei-home-task-progress__bar">
				<div
					role="progressbar"
					aria-label={ __(
						'Sensei Onboarding Progress',
						'sensei-lms'
					) }
					aria-valuenow={ percentage }
					className="sensei-home-task-progress__bar-filled"
					style={ { width: `${ percentage }%` } }
				/>
			</div>
		</div>
	);
};

export default Progress;
