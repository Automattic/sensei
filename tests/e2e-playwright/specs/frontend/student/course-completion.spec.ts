/**
 * External dependencies
 */
import { expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { LessonPage } from '@e2e/pages/frontend/lesson';
import { CoursePage } from '@e2e/pages/frontend/course';
import { studentRole } from '@e2e/helpers/context';
import { test } from '../course-fixtures';
import { CourseMode } from '@e2e/factories/courses';

const { describe, use } = test;

const testCourseWithMode = ( courseMode: CourseMode ) =>
	describe.parallel( `Course Completion in ${ courseMode }`, () => {
		use( { courseMode } );
		use( studentRole() );

		test( 'Student enrolls in course and completes all lessons.', async ( { page, course } ) => {
			const coursePage = new CoursePage( page );
			await page.goto( course.link );

			await coursePage.takeCourse.click();

			// Can access first lesson content.
			const [ lesson, lesson2 ] = course.lessons;
			await page.goto( lesson.link );
			const lessonPage = new LessonPage( page );

			await expect( lessonPage.title ).toHaveText( lesson.title.raw );

			// Completes first lesson.
			await lessonPage.clickCompleteLesson();

			// Second lesson is opened.
			await expect( page ).toHaveURL( lesson2.link );
			await expect( lessonPage.title ).toHaveText( lesson2.title.raw );

			// Completes second lesson.
			await lessonPage.clickCompleteLesson();

			// Course page indicates all lessons are completed.
			await page.goto( course.link );
			await expect( page.locator( 'text=2 of 2 lessons completed (100%)' ) ).toBeVisible();
		} );

		test( 'Student enrolls in course and completes all lessons #2.', async ( { page, course } ) => {
			const coursePage = new CoursePage( page );
			await page.goto( course.link );

			await coursePage.takeCourse.click();

			// Can access first lesson content.
			const [ lesson, lesson2 ] = course.lessons;
			await page.goto( lesson.link );
			const lessonPage = new LessonPage( page );

			await expect( lessonPage.title ).toHaveText( lesson.title.raw );

			// Completes first lesson.
			await lessonPage.clickCompleteLesson();

			// Second lesson is opened.
			await expect( page ).toHaveURL( lesson2.link );
			await expect( lessonPage.title ).toHaveText( lesson2.title.raw );

			// Completes second lesson.
			await lessonPage.clickCompleteLesson();

			// Course page indicates all lessons are completed.
			await page.goto( course.link );
			await expect( page.locator( 'text=2 of 2 lessons completed (100%)' ) ).toBeVisible();
		} );
	} );

for ( const mode of [ CourseMode.LEARNING_MODE, CourseMode.DEFAULT_MODE ] ) {
	testCourseWithMode( mode );
}
