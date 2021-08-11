/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import CourseResultsBlock from './course-results-block';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import { registerCourseCompletedActionsBlock } from './course-completed-actions';

registerCourseCompletedActionsBlock();
registerSenseiBlocks( [
	CourseResultsBlock,
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
] );
