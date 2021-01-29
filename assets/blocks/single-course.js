/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import TakeCourseBlock from './take-course-block';
import CourseProgressBlock from './course-progress-block';
import { OutlineBlock, LessonBlock, ModuleBlock } from './course-outline';
import ConditionalContentBlock from './conditional-content';

registerSenseiBlocks( [
	OutlineBlock,
	ModuleBlock,
	LessonBlock,
	TakeCourseBlock,
	CourseProgressBlock,
	ConditionalContentBlock,
] );
