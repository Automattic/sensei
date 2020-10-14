import { registerBlockType } from '@wordpress/blocks';
import TakeCourseButtonBlock from './take-course';
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
].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
