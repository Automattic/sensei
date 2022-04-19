/* eslint-disable jest/no-done-callback */

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const LoginPage = require( '../pages/login' );
const StudentsPage = require( '../pages/students' );
const CoursesPage = require( '../pages/courses' );

test.describe( 'Students Management', () => {
	const COURSE_NAME = 'My Course';

	test.beforeEach( async ( { page } ) => {
		await new LoginPage( page ).login();
		await new CoursesPage( page ).open();
		await new CoursesPage( page ).createCourse( COURSE_NAME );
	} );

	test( 'should allow me to add a student to a course', async ( {
		page,
	} ) => {
		const studentsPage = new StudentsPage( page );
		await studentsPage.open();
		await studentsPage.openAddToCourseModal();
		await studentsPage.modal
			.locator( `label:has-text("${ COURSE_NAME }")` )
			.check();
		await studentsPage.modal.locator( 'text=Add to Course' ).click();
		return expect(
			page.locator( 'text=1 Course In Progress*' )
		).toBeTruthy();
	} );
} );
