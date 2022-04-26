/* eslint-disable jest/no-done-callback */

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const LoginPage = require( '../pages/admin/login' );
const StudentsPage = require( '../pages/admin/students/students' );
const CoursesPage = require( '../pages/admin/courses' );
const { cleanAll: cleanDatabase } = require( '../helpers/database' );

test.describe( 'Students Management', () => {
	const COURSE_NAME = `My Course${ Math.random() }`;

	test.beforeAll( () => cleanDatabase() );

	test( 'Should allow me to add a student to a course', async ( {
		page,
	} ) => {
		const loginPage = new LoginPage( page );
		const coursePage = new CoursesPage( page );
		const studentsPage = new StudentsPage( page );

		await loginPage.goTo();
		await loginPage.logIn();
		await coursePage.goTo();
		await coursePage.createCourse( COURSE_NAME );
		await studentsPage.goTo();
		await expect( studentsPage.enrolledCoursesColumn ).toHaveText(
			'0 Courses Enrolled'
		);
		await studentsPage.openStudentAction( 'admin', 'Add to Course' );
		await studentsPage.modal.selectCourse( COURSE_NAME );
		await studentsPage.modal.addToCourseButton.click();

		await expect( studentsPage.enrolledCoursesColumn ).toContainText(
			'1 Course Enrolled'
		);
	} );
} );
