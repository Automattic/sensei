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

	test( 'it retains the start date after a failed save and allows retry', async ( { page } ) => {
		await page.goto( `/wp-admin/admin.php?page=sensei_learners&view=learners&course_id=${ course.id }` );
		const row = page.locator( 'tr' ).filter( {
			has: page.locator( `.edit-start-date-submit[data-user-id="${ student.id }"]` ),
		} );
		const date = row.locator( '.edit-date-date-picker[data-name="start-date"]' );
		const update = row.getByText( 'Update Student', { exact: true } );
		const enteredDate = '2025-06-15 10:30:00';
		let failSave = true;
		await page.route( '**/admin-ajax.php', async ( route ) => {
			if ( failSave && route.request().postData()?.includes( 'action=edit_date_started' ) ) {
				failSave = false;
				await route.fulfill( { status: 500, body: 'Failed to save.' } );
			} else {
				await route.continue();
			}
		} );

		await date.fill( enteredDate );
		await update.click();

		await expect( page.getByRole( 'alert' ) ).toContainText( "We couldn't save this start date. Try again." );
		await expect( page.locator( '.sensei-edit-date-error + table' ) ).toContainText( student.username );
		await expect( date ).toHaveValue( enteredDate );
		await expect( update ).toBeEnabled();

		await update.click();

		await expect( date ).toHaveAttribute( 'value', enteredDate );
		await expect( page.getByRole( 'alert' ) ).toHaveCount( 0 );
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
