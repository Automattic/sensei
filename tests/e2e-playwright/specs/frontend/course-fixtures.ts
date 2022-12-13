/**
 * External dependencies
 */
import { test as base } from '@playwright/test';
/**
 * Internal dependencies
 */
import { CourseDefinition, createCourse } from '@e2e/helpers/api';
import { asAdmin } from '@e2e/helpers/context';

import { lessonSimple } from '@e2e/helpers/lesson-templates';

export enum CourseMode {
	LEARNING_MODE = 'Learning Mode',
	DEFAULT_MODE = 'Default Mode',
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
	courseMode: [ CourseMode.DEFAULT_MODE, { option: true } ],
	course: async ( { browser, courseMode }, use ) => {
		const course = ( await asAdmin( { browser }, async ( { context } ) => {
			return createCourse(
				context.request,
				createCourseDef( courseMode )
			);
		} ) ) as Course;

		await use( course );

		return course;
	},
} );

const LEARNING_MODE_META = {
	_course_theme: 'sensei-theme',
};

export const createCourseDef = ( courseMode: CourseMode ): CourseDefinition => {
	const useLearningMode = courseMode === CourseMode.LEARNING_MODE;

	return {
		title: `E2E Course ${ courseMode }`,
		meta: {
			...( useLearningMode ? LEARNING_MODE_META : {} ),
		},
		lessons: [
			{
				title: `E2E Lesson ${ courseMode } 101`,
				content: lessonSimple(),
			},
			{
				title: `E2E Lesson ${ courseMode } 102`,
				content: lessonSimple(),
			},
		],
	};
};
