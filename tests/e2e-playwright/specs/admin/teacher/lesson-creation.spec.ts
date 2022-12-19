/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

import LessonList from '@e2e/pages/admin/lessons';
import { adminRole } from '@e2e/helpers/context';
import { createCourse } from '@e2e/helpers/api';

const { describe, use, beforeAll } = test;

describe( 'Create Lessons', () => {
	use( adminRole() );

	// setup: create a coures using API
	beforeAll( async ( { request } ) => {
		await createCourse( request, {
			title: 'Test Course One',
			lessons: [],
		} );
	} );

	test( 'creates a lesson for a course', async ( {
		page,
	}: {
		page: Page;
	} ) => {
		const lessonList = new LessonList( page );
		lessonList.goToPostTypeListingPage();

		const lessonEdit = await lessonList.clickNewLesson();

		// close welcome for the first time
		await page.locator( '[aria-label="Close dialog"]' ).click();

		const wizardModal = await lessonEdit.wizardModal;
		await wizardModal.input.fill( 'Test Lesson One' );
		await wizardModal.continueButton.click();
		await wizardModal.startWithDefaultLayoutButton.click();

		await lessonEdit.addLessonContent( 'Test Lesson Content' );

		// close welcome dialog for the second time
		await page.locator( '[aria-label="Close dialog"]' ).click();

		await lessonEdit.clickPublish();

		const previewPage = await lessonEdit.clickViewLesson();

		await expect(
			previewPage.locator( 'h1:has-text("Test Lesson One")' )
		).toBeVisible();
		await expect(
			previewPage.locator( 'p:has-text("Test Lesson Content")' )
		).toBeVisible();
	} );
} );
