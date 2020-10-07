import { registerBlockType } from '@wordpress/blocks';
import TakeCourseButtonBlock from './button/take-course';
import { CourseOutlineBlock } from './course-outline';

[ CourseOutlineBlock, TakeCourseButtonBlock ].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
