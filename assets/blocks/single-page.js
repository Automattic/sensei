/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import { registerCourseCompletedActionsBlock } from './course-completed-actions';
import CourseResultsBlock from './course-results-block';

registerCourseCompletedActionsBlock();

registerSenseiBlocks( [
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
	CourseResultsBlock,
] );
