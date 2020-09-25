import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { LessonIcon } from '../../../icons';
import EditLessonBlock from './edit';

registerBlockType( 'sensei-lms/course-outline-lesson', {
	title: __( 'Lesson', 'sensei-lms' ),
	description: __( 'Where your course content lives.', 'sensei-lms' ),
	icon: LessonIcon,
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline', 'sensei-lms/course-outline-module' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Lesson', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: true,
	},
	example: {
		attributes: {
			title: 'Start learning',
		},
	},
	attributes: {
		id: {
			type: 'integer',
		},
		title: {
			type: 'string',
			default: '',
		},
		draft: {
			type: 'boolean',
			default: true,
		},
		backgroundColor: {
			type: 'string',
		},
		customBackgroundColor: {
			type: 'string',
		},
		textColor: {
			type: 'string',
		},
		customTextColor: {
			type: 'string',
		},
		fontSize: {
			type: 'number',
		},
	},
	edit( props ) {
		return <EditLessonBlock { ...props } />;
	},
	save( { className } ) {
		return <div className={ className } />;
	},
} );
