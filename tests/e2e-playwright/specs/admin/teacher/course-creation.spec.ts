/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect, test } from '@playwright/test';

import CoursesPage from '@e2e/pages/admin/courses';
import Dashboard from '@e2e/pages/admin/dashboard';
import { teacherRole } from '@e2e/helpers/context';

const { describe, use } = test;

describe.parallel( 'Create Courses', () => {
	use( teacherRole() );

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

		// Currently we are saving the course status after the course wizard is closed, it moving the focus out of the course outline block.
		await page.getByRole( 'button', { name: 'Save draft' } ).isVisible();

		await coursesPage.addModuleWithLesson(
			'Module 1',
			'Lesson 1 in Module 1'
		);

		await coursesPage.submitForPreview();

		const previewPage = await coursesPage.goToPreview();

		await expect(
			previewPage.locator( 'h1:has-text("Test Create Course")' )
		).toBeVisible();
		await expect(
			previewPage.getByRole( 'heading', { name: 'Module 1' } )
		).toBeVisible();
		await expect(
			previewPage.locator( 'text="Lesson 1 in Module 1"' )
		).toBeVisible();
		await expect(
			previewPage.locator( 'button:has-text("Take Course")' )
		).toBeVisible();
	} );
} );
