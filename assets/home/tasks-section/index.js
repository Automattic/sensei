/**
 * Internal dependencies
 */
import Section from '../section';
import Progress from './progress';
import Tasks from './tasks';
import FirstCourse from './first-course';
import Ready from './ready';

/**
 * Tasks section component.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.data Tasks data.
 */
const TasksSection = ( { data } ) => {
	const items = Object.values( data.items );

	const sortedItems = items.sort( ( a, b ) => a.priority - b.priority );
	const completedItems = sortedItems.filter( ( i ) => i.done );

	return (
		<Section
			className="sensei-home-tasks-section"
			insideClassName="sensei-home-tasks-section__inside"
		>
			{ data.is_completed ? (
				<Ready />
			) : (
				<>
					<div className="sensei-home-tasks-section__content">
						<Progress
							totalTasks={ items.length }
							completedTasks={ completedItems.length }
						/>
						<Tasks items={ sortedItems } />
					</div>
					<div className="sensei-home-tasks-section__first-course">
						<FirstCourse
							siteTitle="Learn Photography"
							courseTitle="Architectural Photography"
							siteLogo="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
							featuredImage="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
						/>
					</div>
				</>
			) }
		</Section>
	);
};

export default TasksSection;
