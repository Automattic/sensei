/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import CourseResultsBlock from './course-results-block';

registerSenseiBlocks( [
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
	CourseResultsBlock,
] );
