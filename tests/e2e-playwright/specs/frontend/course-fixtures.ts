/**
 * External dependencies
 */
import { test as base } from '@playwright/test';

/**
 * Internal dependencies
 */
import { createCourse } from '../../helpers/api';
import { asAdmin } from '../../helpers/context';

import { lessonNoLessonActions, lessonSimple } from '../../helpers/lesson-templates';

export enum CourseMode {
	blocks = 'Blocks Mode',
	learningMode = 'Learning Mode',
	templates = 'Templates Mode',
}

export type Course = {
	title: { raw: string; rendered: string };
	content: { raw: string; rendered: string };
	lessons: Lesson[];
	id: number;
	link: string;
};

export type Lesson = {
	title: { raw: string; rendered: string };
	content: { raw: string; rendered: string };
	id: number;
	link: string;
};

export const test = base.extend< { course: Course; courseMode: CourseMode } >( {
	courseMode: [ CourseMode.blocks, { option: true } ],
	course: async ( { browser, courseMode }, use ) => {
		const course = await asAdmin( { browser }, async ( { context } ) => {
			return createCourse( context.request, createCourseDef( courseMode ) );
		} );

		await use( course );

		return course;
	},
} );

export const createCourseDef = ( courseMode ) => {
	const lessonContent = CourseMode.templates === courseMode ? lessonNoLessonActions() : lessonSimple();
	const meta: any = {};

	if ( CourseMode.learningMode === courseMode ) {
		meta._course_theme = 'sensei-theme';
	}

	return {
		title: `E2E Course ${ courseMode }`,
		meta,
		lessons: [
			{
				title: `E2E Lesson ${ courseMode } 101`,
				content: lessonContent,
			},
			{
				title: `E2E Lesson ${ courseMode } 102`,
				content: lessonContent,
			},
		],
	};
};
