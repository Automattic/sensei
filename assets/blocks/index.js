import blocksSetup from './blocks-setup';
import TakeCourseButtonBlock from './take-course';
import ContactTeacherButton from './contact-teacher';
import CourseProgressBlock from './course-progress';
import {
	CourseOutlineBlock,
	CourseOutlineLessonBlock,
	CourseOutlineModuleBlock,
} from './course-outline';

blocksSetup( [
	CourseOutlineBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
	TakeCourseButtonBlock,
	ContactTeacherButton,
	CourseProgressBlock,
] );
