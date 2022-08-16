/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import CourseProgressBlock from './course-progress-block';
import { ContinueCourse, CourseActions } from './course-actions-block';
import CourseResultsBlock from './course-results-block';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import TakeCourseBlock from './take-course-block';
import ViewResultsBlock from './view-results-block';
import { registerCourseCompletedActionsBlock } from './course-completed-actions';
import { registerCourseListBlock } from './course-list-block';
import CourseCategoryBlock from './course-categories-block';

registerCourseCompletedActionsBlock();

registerCourseListBlock();

registerSenseiBlocks( [
	ContinueCourse,
	CourseActions,
	CourseProgressBlock,
	CourseResultsBlock,
	LearnerCoursesBlock,
	LearnerMessagesButtonBlock,
	TakeCourseBlock,
	ViewResultsBlock,
	CourseCategoryBlock,
] );
