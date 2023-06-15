/**
 * External dependencies
 */
import { test as base } from '@playwright/test';
/**
 * Internal dependencies
 */
import { asAdmin, createCourse } from '@e2e/helpers/api';
import type { CourseResponse } from '@e2e/helpers/api';
import { buildCourse, CourseMode } from '@e2e/factories/courses';

export const test = base.extend< {
	course: CourseResponse;
	courseMode: CourseMode;
} >( {
	courseMode: [ CourseMode.DEFAULT_MODE, { option: true } ],
	course: async ( { courseMode }, use ) => {
		let course = null;
		await asAdmin( async ( api ) => {
			course = await createCourse( api, buildCourse( courseMode ) );

			await use( course );
		} );

		return course;
	},
} );
