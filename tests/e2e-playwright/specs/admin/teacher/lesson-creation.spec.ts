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

	beforeAll( async ( { request } ) => {
		const courseContent = {
			title: 'Test Course One',
			lessons: [],
		};
		await createCourse( request, courseContent );
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
		await page.waitForSelector( '[aria-label="Close dialog"]' );
		await page.locator( '[aria-label="Close dialog"]' ).click();

		const wizardModal = await lessonEdit.wizardModal;
		await wizardModal.input.fill( 'Test Lesson One' );
		await wizardModal.continueButton.click();
		await wizardModal.startWithDefaultLayoutButton.click();

		await lessonEdit.addLessonContent( 'Test Lesson Content' );
		await lessonEdit.setLessonCourse( 'Test Course One' );

		await lessonEdit.clickSaveDraft();
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
