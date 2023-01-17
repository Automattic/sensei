/**
 * External dependencies
 */
import { test as base } from '@playwright/test';
/**
 * Internal dependencies
 */
import { createCourse } from '@e2e/helpers/api';
import type { CourseResponse } from '@e2e/helpers/api';
import { useAdminContext } from '@e2e/helpers/context';
import { buildCourse, CourseMode } from '@e2e/factories/courses';

export const test = base.extend< {
	course: CourseResponse;
	courseMode: CourseMode;
} >( {
	courseMode: [ CourseMode.DEFAULT_MODE, { option: true } ],
	course: async ( { browser, courseMode }, use ) => {
		const context = await useAdminContext( browser );
		const course = await createCourse( context, buildCourse( courseMode ) );

		await use( course );

		return course;
	},
} );
