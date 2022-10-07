/**
 * Internal dependencies
 */
import Section from '../section';
import Tasks from '../tasks/tasks';
import Progress from '../tasks/progress';

/**
 * Task List section component.
 */
const TaskList = () => (
	<Section>
		<Progress totalTasks={ 10 } completedTasks={ 5 } />
		<Tasks />
	</Section>
);

export default TaskList;
