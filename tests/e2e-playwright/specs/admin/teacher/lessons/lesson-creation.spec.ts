/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect } from '@playwright/test';

import LessonList from '@e2e/pages/admin/lessons';
import { test } from './fixture';

const { describe } = test;

describe( 'Create Lessons', () => {
	test( 'creates a lesson for a course', async ( {
		page,
		approvedCourse: course,
	} ) => {
		const lessonList = new LessonList( page );
		lessonList.goTo();

		const lessonEdit = await lessonList.clickNewLesson();

		const wizardModal = await lessonEdit.wizardModal;
		await wizardModal.lessonTitle.fill( 'Test Lesson One' );

		await wizardModal.continueButton.click();
		await wizardModal.startWithDefaultLayoutButton.click();

		await lessonEdit.addLessonContent( 'Test Lesson Content' );
		await lessonEdit.setLessonCourse( course.title.rendered );

		await lessonEdit.clickPublish();

		await page.goto( course.link );
		await expect( page.getByText( 'Test Lesson One' ) ).toBeVisible();
	} );
} );
