/**
 * Internal dependencies
 */
import Section from '../section';
import Tasks from '../tasks/tasks';
import Progress from '../tasks/progress';
import FirstCourse from '../tasks/first-course';

/**
 * Task List section component.
 */
const TaskList = () => (
	<Section>
		<Progress totalTasks={ 10 } completedTasks={ 5 } />
		<Tasks />
		<FirstCourse
			siteTitle="Learn Photography"
			courseTitle="Architectural Photography"
			siteLogo="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
			featuredImage="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
		/>
	</Section>
);

export default TaskList;
