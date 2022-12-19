/**
 * External dependencies
 */
import { test as base } from '@playwright/test';
/**
 * Internal dependencies
 */
import { approveCourse, createCourse } from '@e2e/helpers/api';
import type { CourseResponse } from '@e2e/helpers/api';
import { getContextByRole, useAdminContext } from '@e2e/helpers/context';
import { buildCourse, CourseMode } from '@e2e/factories/courses';

export const test = base.extend< {
	approvedCourse: CourseResponse;
} >( {
	approvedCourse: async ( { request, browser }, use ) => {
		const created = await createCourse(
			request,
			buildCourse( CourseMode.DEFAULT_MODE, {
				status: 'pending',
				title: 'My new Course',
				lessons: [],
			} )
		);

		const adminContext = await useAdminContext( browser );
		await approveCourse( adminContext, created.id );

		await use( created );
	},
	storageState: async ( {}, use ) =>
		await use( getContextByRole( 'teacher' ) ),
} );
