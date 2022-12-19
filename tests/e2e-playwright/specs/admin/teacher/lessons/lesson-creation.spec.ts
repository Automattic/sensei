/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect } from '@playwright/test';

import LessonList from '@e2e/pages/admin/lessons';
import { test } from './fixture';
import { CoursePage as FrontEndCoursePage } from '@e2e/pages/frontend/course';
import {
	getContextByRole,
	studentRole as studentContext,
} from '@e2e/helpers/context';

const { describe } = test;

describe.serial( 'Create Default Lesson', () => {
	test( 'creates a lesson for a course', async ( {
		page,
		browser,
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

		await lessonEdit.publish();
		const coursePage = new FrontEndCoursePage( page, course.link );
		await coursePage.goTo();
		await coursePage.takeCourse.click();

		await expect(
			page
				.locator( '.sensei-course-theme__main-content' )
				.getByText( 'Test Lesson One' )
		).toBeVisible();
		await expect( page.getByText( 'Test Lesson Content' ) ).toBeVisible();
	} );
} );
