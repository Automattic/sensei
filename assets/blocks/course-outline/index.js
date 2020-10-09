import { registerBlockType } from '@wordpress/blocks';
import CourseOutlineBlock from './course-block';
import ModuleBlock from './module-block';
import LessonBlock from './lesson-block';
import './store';

[ CourseOutlineBlock, ModuleBlock, LessonBlock ].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
