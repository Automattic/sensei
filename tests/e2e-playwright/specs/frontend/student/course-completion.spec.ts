/**
 * External dependencies
 */
import { expect, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { LessonPage } from '../../../pages/frontend/lesson';
import { CoursePage } from '../../../pages/frontend/course';
import { asAdmin, studentRole } from '../../../helpers/context';
import { CourseMode, test } from '../course-fixtures';
import { disableGlobalLearningMode } from '../../../helpers/api';
import { text as lessonTextContent } from '../../../helpers/lesson-templates';

const { describe, use, beforeAll } = test;

const testCourseWithMode = ( courseMode: CourseMode ) =>
	describe( `Course Completion in ${ courseMode }`, () => {
		use( { courseMode } );
		use( studentRole() );

		beforeAll( async ( { browser } ) => {
			await asAdmin( { browser }, async ( { context } ) => {
				await disableGlobalLearningMode( context );
			} );
		} );

		test( 'Student enrolls in course and completes all lessons.', async ( { page, course } ) => {
			const coursePage = new CoursePage( page );
			await page.goto( course.link );

			await coursePage.takeCourse.click( { force: true } );

			await expect( coursePage.takeCourse ).toBeHidden();

			// Can access first lesson content.
			const [ lesson, lesson2 ] = course.lessons;
			await page.goto( lesson.link );
			const lessonPage = new LessonPage( page );

			await expect( lessonPage.title ).toHaveText( lesson.title.raw );
			await expect( page.locator( '.entry-content p' ).first() ).toHaveText( lessonTextContent );

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

for ( const mode of [ CourseMode.learningMode, CourseMode.templates, CourseMode.blocks ] ) {
	testCourseWithMode( mode );
}
