/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import { registerCourseListBlock } from './course-list-block';
import { ContinueCourse, CourseActions } from './course-actions-block';
import CourseCategoriesBlock from './course-categories-block';
import CourseListFilterBlock from './course-list-filter-block';
import CourseOverviewBlock from './course-overview-block';
import CourseProgressBlock from './course-progress-block';
import TakeCourseBlock from './take-course-block';
import ViewResultsBlock from './view-results-block';

registerCourseListBlock();

registerSenseiBlocks( [
	ContinueCourse,
	CourseActions,
	CourseCategoriesBlock,
	CourseListFilterBlock,
	CourseOverviewBlock,
	CourseProgressBlock,
	TakeCourseBlock,
	ViewResultsBlock,
] );
