import registerSenseiBlocks from './register-sensei-blocks';
import TakeCourseButtonBlock from './take-course';
import CourseProgressBlock from './course-progress';
import RestrictedContent from './restricted-content';
import {
	CourseOutlineBlock,
	CourseOutlineLessonBlock,
	CourseOutlineModuleBlock,
} from './course-outline';

registerSenseiBlocks( [
	CourseOutlineBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
	TakeCourseButtonBlock,
	CourseProgressBlock,
	RestrictedContent,
] );
