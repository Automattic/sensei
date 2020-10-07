import { registerBlockType } from '@wordpress/blocks';
import CourseOutlineBlock from './course-block';
import './module-block';
import './lesson-block';
import './store';
import '../button.core';
import '../button.own';

[ CourseOutlineBlock ].forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
