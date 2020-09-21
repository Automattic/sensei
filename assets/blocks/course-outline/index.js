import { registerBlockType } from '@wordpress/blocks';
import CourseOutlineBlock from './course-block';
import './module-block';
import './lesson-block';
import './store';

[ CourseOutlineBlock ].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
