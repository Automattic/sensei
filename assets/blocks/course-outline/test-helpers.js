/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

export const registerTestLessonBlock = ( settings = {} ) => {
	registerBlockType( 'sensei-lms/course-outline-lesson', {
		title: 'Lesson Test',
		parent: [
			'sensei-lms/course-outline',
			'sensei-lms/course-outline-module',
		],
		category: 'layout',
		attributes: {
			id: {
				type: 'int',
			},
			title: {
				type: 'string',
				default: '',
			},
			style: {
				type: 'object',
				default: {},
			},
		},
		...settings,
	} );
};

export const registerTestModuleBlock = ( settings = {} ) => {
	registerBlockType( 'sensei-lms/course-outline-module', {
		title: 'Module Test',
		parent: [
			'sensei-lms/course-outline',
			'sensei-lms/course-outline-module',
		],
		category: 'layout',
		attributes: {
			id: {
				type: 'int',
			},
			title: {
				type: 'string',
				default: '',
			},
			description: {
				type: 'string',
				default: '',
			},
			style: {
				type: 'object',
				default: {},
			},
		},
		...settings,
	} );
};
