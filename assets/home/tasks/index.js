/**
 * Internal dependencies
 */
import Section from '../section';
import Progress from './progress';
import Tasks from './tasks';
import FirstCourse from './first-course';
// import Ready from './ready';

const TasksSection = () => (
	<Section
		className="sensei-home-tasks-section"
		insideClassName="sensei-home-tasks-section__inside"
	>
		<div className="sensei-home-tasks-section__content">
			<Progress totalTasks={ 10 } completedTasks={ 5 } />
			<Tasks />
		</div>

		<div className="sensei-home-tasks-section__first-course">
			<FirstCourse
				siteTitle="Learn Photography"
				courseTitle="Architectural Photography"
				siteLogo="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
				featuredImage="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
			/>
		</div>
		{ /* <Ready /> */ }
	</Section>
);

export default TasksSection;
