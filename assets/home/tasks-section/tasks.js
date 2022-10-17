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
 *
 * @param {Object}   props       Component props.
 * @param {Object[]} props.items The tasks.
 */
const Tasks = ( { items } ) => (
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
			{ items.map( ( task ) => (
				<TaskItem
					key={ task.id }
					title={ task.title }
					url={ task.url }
					done={ task.done }
				/>
			) ) }
		</ul>
	</div>
);

export default Tasks;
