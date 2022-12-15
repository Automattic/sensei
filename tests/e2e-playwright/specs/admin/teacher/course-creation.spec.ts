/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { getContextByRole } from '@e2e/helpers/context';
import CoursesPage from '@e2e/pages/admin/courses';
import Dashboard from '@e2e/pages/admin/dashboard';

const { describe, use } = test;

describe( 'Create Courses', () => {
	use( { storageState: getContextByRole( 'teacher' ) } );

	test( 'it has a Courses menu item in the main menu', async ( { page } ) => {
		const dashboard = new Dashboard( page );
		await dashboard.goTo();
		const senseiMenuItem = await dashboard.getSenseiMenuItem();
		await senseiMenuItem.click();

		const coursesMenuItem = await dashboard.getCoursesMenuItem();
		await coursesMenuItem.click();

		await expect(
			page.locator( '.sensei-custom-navigation__title h1' )
		).toHaveText( 'Courses' );
	} );

	test( 'it should create a course', async ( { page } ) => {
		const coursesPage = new CoursesPage( page );
		coursesPage.goToPostTypeListingPage();

		await coursesPage.createCourseButton.click();

		// Close Welcome to the block editor dialog.
		await coursesPage.dialogCloseButton.click();

		// Fill in the course title and description.
		const wizardModal = coursesPage.wizardModal;
		await wizardModal.input.fill( 'Test Create Course' );
		await wizardModal.textArea.fill( 'Test Create Course Description' );

		// Click "Continue" button.
		await wizardModal.continueButton.click();
		// Click "Continue with Sensei Free" button.
		await wizardModal.continueWithFreeButton.first().click();
		// Click "Start with default layout" button.
		await wizardModal.startWithDefaultLayoutButton.click();

		await coursesPage.addModuleWithLesson(
			'Module 1',
			'Lesson 1 in Module 1'
		);

		// Publish the course (publish method doesn't work as there is no redirect at this point).
		await coursesPage.publishButton.click();
		await coursesPage.confirmPublishButton.click();

		await coursesPage.viewPreviewLink.click();

		await expect(
			page.locator( 'h1:has-text("Test Create Course")' )
		).toBeVisible();
		await expect( page.locator( 'text="Module 1"' ) ).toBeVisible();
		await expect(
			page.locator( 'text="Lesson 1 in Module 1"' )
		).toBeVisible();
		await expect(
			page.locator( 'button:has-text("Take Course")' )
		).toBeVisible();
	} );
} );
