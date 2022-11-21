/**
 * External dependencies
 */
import { expect, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { LessonPage } from '../../pages/frontend/lesson';
import { CoursePage } from '../../pages/frontend/course';
import { asAdmin, studentRole } from '../../helpers/context';
import { Course, CourseMode, test } from './course-fixtures';
import { disableGlobalLearningMode } from '../../helpers/api';
import { text as lessonTextContent } from '../../helpers/lesson-templates';

const { describe, use, beforeAll } = test;

async function enroll( page: Page, course: Course ) {
	const coursePage = new CoursePage( page );
	await page.goto( course.link );
	await coursePage.takeCourse.click( { force: true } );
}

const testMode = ( courseMode: CourseMode ) =>
	describe( `Course Frontend (${ courseMode })`, () => {
		use( { courseMode } );
		use( studentRole() );

		beforeAll( async ( { browser } ) => {
			await asAdmin( { browser }, async ( { context } ) => {
				await disableGlobalLearningMode( context );
			} );
		} );

		test( 'Student enrolls in course', async ( { page, course } ) => {
			const coursePage = new CoursePage( page );
			await page.goto( course.link );

			await coursePage.takeCourse.click( { force: true } );

			await expect( coursePage.takeCourse ).toBeHidden();

			// Can access first lesson content.
			const lesson = course.lessons[ 0 ];
			await page.goto( lesson.link );
			await expect( page.locator( '.entry-content p' ).first() ).toHaveText( lessonTextContent );
		} );

		test( 'Student completes lesson', async ( { page, course } ) => {
			await enroll( page, course );

			const [ lesson, lesson2 ] = course.lessons;
			await page.goto( lesson.link );
			const lessonPage = new LessonPage( page );

			await expect( lessonPage.title ).toHaveText( lesson.title.raw );

			await lessonPage.clickCompleteLesson();

			// Opens next lesson.
			await expect( page ).toHaveURL( lesson2.link );
			return await expect( lessonPage.title ).toHaveText( lesson2.title.raw );
		} );

		test( 'Student completes course', async ( { page, course } ) => {
			await enroll( page, course );
			const lessonPage = new LessonPage( page );

			for ( const lesson of course.lessons ) {
				await page.goto( lesson.link );
				await lessonPage.clickCompleteLesson();
			}

			await page.goto( course.link );

			await expect( page.locator( 'text=2 of 2 lessons completed (100%)' ) ).toBeVisible();
		} );
	} );

for ( const mode of [ CourseMode.learningMode, CourseMode.templates, CourseMode.blocks ] ) {
	testMode( mode );
}
