/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TaskItem from './task-item';

/**
 * Tasks component.
 */
const Tasks = () => (
	<div className="sensei-home-tasks">
		<h1 className="sensei-home-tasks__title">
			{ __( 'Welcome to your new Sensei course site.', 'sensei-lms' ) }
		</h1>

		<p className="sensei-home-tasks__description">
			{ __(
				'Keep the momentum going and let’s get your first Course in front of your students.',
				'sensei-lms'
			) }
		</p>

		<ul className="sensei-home-tasks__list">
			<TaskItem label="Set up Course Site" href="#" completed />
			<TaskItem label="Create your first Course" href="#" completed />
			<TaskItem label="Create your first Course" href="#" />
			<TaskItem label="Configure Learning Mode" href="#" />
			<TaskItem label="Publish your first Course" href="#" />
		</ul>
	</div>
);

export default Tasks;
