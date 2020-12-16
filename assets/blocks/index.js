import { registerBlockType, updateCategory } from '@wordpress/blocks';
import TakeCourseButtonBlock from './take-course';
import ContactTeacherButton from './contact-teacher';
import CourseProgressBlock from './course-progress';
import {
	CourseOutlineBlock,
	CourseOutlineLessonBlock,
	CourseOutlineModuleBlock,
} from './course-outline';
import { SenseiIcon } from '../icons';

updateCategory( 'sensei-lms', {
	icon: SenseiIcon( { width: '20', height: '20' } ),
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
