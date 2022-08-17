/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import CourseProgressBlock from './course-progress-block';
import { ContinueCourse, CourseActions } from './course-actions-block';
import CourseResultsBlock from './course-results-block';
import TakeCourseBlock from './take-course-block';
import ViewResultsBlock from './view-results-block';
import { registerCourseListBlock } from './course-list-block';

registerCourseListBlock();

registerSenseiBlocks( [
	ContinueCourse,
	CourseActions,
	CourseProgressBlock,
	CourseResultsBlock,
	TakeCourseBlock,
	ViewResultsBlock,
] );
