/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import CourseProgressBlock from './course-progress-block';
import CourseResultsBlock from './course-results-block';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import { registerCourseCompletedActionsBlock } from './course-completed-actions';
import { registerCourseListBlock } from './course-list-block';
import CourseCategoryBlock from './course-categories-block';

registerCourseCompletedActionsBlock();

registerCourseListBlock();

registerSenseiBlocks( [
	CourseProgressBlock,
	CourseResultsBlock,
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
	CourseCategoryBlock,
] );
