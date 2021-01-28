/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import TakeCourseBlock from './take-course-block';
import CourseProgressBlock from './course-progress-block';
import { OutlineBlock, LessonBlock, ModuleBlock } from './course-outline';
import RestrictedContentBlock from './restricted-content-block';

registerSenseiBlocks( [
	OutlineBlock,
	ModuleBlock,
	LessonBlock,
	TakeCourseBlock,
	CourseProgressBlock,
	RestrictedContentBlock,
] );
