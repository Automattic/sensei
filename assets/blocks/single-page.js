/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import { registerCourseCompletedActionsBlock } from './course-completed-actions';
import { registerCourseListBlock } from './course-list-block';
import CourseResultsBlock from './course-results-block';

registerCourseCompletedActionsBlock();

registerCourseListBlock();

registerSenseiBlocks( [
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
	CourseResultsBlock,
] );
