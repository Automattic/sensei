/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import StudentsPage from '@e2e/pages/admin/students/students';
import { createCourse, createUser } from '@e2e/helpers/api';
import type { User } from '@e2e/helpers/api';
import { adminRole } from '@e2e/helpers/context';
import { asAdmin } from '@e2e/helpers/api/contexts';

test.describe.serial( 'Students Management', () => {
	test.use( adminRole() );

	const COURSE_NAME = `Course #${ Math.ceil( Math.random() * 100 ) }`;

	const STUDENT: User = {
		username: `student${ Math.ceil( Math.random() * 100 ) }`,
		password: 'password',
	};

	let student, course;

	// it is ensuring the browser is using a admin session.

	test.beforeAll( async () => {
		await asAdmin( async ( request ) => {
			student = await createUser( request, STUDENT );

			course = await createCourse( request, {
				title: COURSE_NAME,
				lessons: [],
			} );
		} );
	} );

	test( 'it should add a student to a course', async ( { page } ) => {
		const studentsPage = new StudentsPage( page );
		await studentsPage.goTo();

		await studentsPage.openStudentAction(
			student.username,
			'Add to Course'
		);

		await studentsPage.modal.selectCourse( course.title.raw );
		await studentsPage.modal.addToCourseButton.click();

		await expect(
			await studentsPage.getRowByStudent( student.username )
		).toContainText( COURSE_NAME );
	} );

	test( 'it should remove the student from course', async ( { page } ) => {
		const studentsPage = new StudentsPage( page );
		await studentsPage.goTo();

		await studentsPage.openStudentAction(
			student.username,
			'Remove From Course'
		);

		await studentsPage.modal.selectCourse( course.title.raw );
		await studentsPage.modal.removeFromCourseButton.click();

		await expect(
			await studentsPage.getRowByStudent( student.username )
		).not.toContainText( COURSE_NAME );
	} );
} );
