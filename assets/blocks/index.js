import { registerBlockType } from '@wordpress/blocks';
import TakeCourseButtonBlock from './button/take-course';
import {
	CourseOutlineBlock,
	CourseOutlineModulesBlock,
	CourseOutlineLessonsBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
} from './course-outline';

[
	CourseOutlineBlock,
	CourseOutlineModulesBlock,
	CourseOutlineLessonsBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
	TakeCourseButtonBlock,
].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
