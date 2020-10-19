import { registerBlockType } from '@wordpress/blocks';
import TakeCourseButtonBlock from './button/take-course';
import CourseProgressBlock from './course-progress';
import {
	CourseOutlineBlock,
	CourseOutlineLessonBlock,
	CourseOutlineModuleBlock,
} from './course-outline';

[
	CourseOutlineBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
	TakeCourseButtonBlock,
	CourseProgressBlock,
].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
