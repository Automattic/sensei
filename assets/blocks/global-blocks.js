/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import CourseProgressBlock from './course-progress-block';
import { ContinueCourse, CourseActions } from './course-actions-block';
import TakeCourseBlock from './take-course-block';
import ViewResultsBlock from './view-results-block';
import { registerCourseListBlock } from './course-list-block';
import CourseCategoriesBlock from './course-categories-block';

registerCourseListBlock();

registerSenseiBlocks( [
	ContinueCourse,
	CourseActions,
	CourseProgressBlock,
	TakeCourseBlock,
	ViewResultsBlock,
	CourseCategoriesBlock,
] );
