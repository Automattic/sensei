import registerSenseiBlocks from './register-sensei-blocks';
import TakeCourseButtonBlock from './take-course';
import ContactTeacherButton from './contact-teacher';
import CourseProgressBlock from './course-progress';
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
	ContactTeacherButton,
	CourseProgressBlock,
] );
