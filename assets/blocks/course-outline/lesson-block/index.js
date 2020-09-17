import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import EditLessonBlock from './edit';

registerBlockType( 'sensei-lms/course-outline-lesson', {
	title: __( 'Lesson', 'sensei-lms' ),
	description: __( 'Where your course content lives.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline', 'sensei-lms/course-outline-module' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Lesson', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	attributes: {
		id: {
			type: 'int',
		},
		title: {
			type: 'string',
			default: '',
		},
	},
	edit( props ) {
		return <EditLessonBlock { ...props } />;
	},
	save( { className } ) {
		return <div className={ className } />;
	},
} );
