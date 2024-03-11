/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect, test } from '@playwright/test';

import CoursesPage from '@e2e/pages/admin/courses';
import Dashboard from '@e2e/pages/admin/dashboard';
import { teacherRole } from '@e2e/helpers/context';

const { describe, use } = test;

describe( 'Create Courses', () => {
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

		await wizardModal.setCourse( {
			title: 'Test Create Course',
			description: 'Test Create Course Description',
		} );

		await wizardModal.finishWithDefaultLayout();

		await page
			.getByRole( 'button', { name: 'Start with blank' } )
			.dispatchEvent( 'click' );

		await coursesPage.saveDraft();

		await coursesPage.addModuleWithLesson(
			'Module 1',
			'Lesson 1 in Module 1'
		);

		await coursesPage.submitForPreview();
		const preview = await coursesPage.goToPreview();

		await expect(
			preview.getByRole( 'heading', { name: 'Test Create Course' } )
		).toBeVisible();

		await expect(
			preview.getByRole( 'heading', { name: 'Module 1' } )
		).toBeVisible();

		await expect(
			preview.getByText( 'Lesson 1 in Module 1' )
		).toBeVisible();

		await expect(
			preview.getByRole( 'button', { name: 'Take Course' } )
		).toBeVisible();
	} );
} );
