import { registerBlockType, updateCategory } from '@wordpress/blocks';
import TakeCourseButtonBlock from './take-course';
import ContactTeacherButton from './contact-teacher';
import CourseProgressBlock from './course-progress';
import {
	CourseOutlineBlock,
	CourseOutlineLessonBlock,
	CourseOutlineModuleBlock,
} from './course-outline';
import { CourseIcon } from '../icons';

updateCategory( 'sensei-lms', {
	icon: CourseIcon( { width: '24', height: '24' } ),
} );

[
	CourseOutlineBlock,
	CourseOutlineModuleBlock,
	CourseOutlineLessonBlock,
	TakeCourseButtonBlock,
	ContactTeacherButton,
	CourseProgressBlock,
].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
