/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';
/**
 * Internal dependencies
 */
import { createCourse, createCourseCategory } from '@e2e/helpers/api';
import PostType from '@e2e/pages/admin/post-type';
import { editorRole } from '@e2e/helpers/context';
import { asAdmin } from '@e2e/helpers/api';

const { describe, use, beforeAll } = test;

describe( 'Courses List Block', () => {
	use( editorRole() );

	const courses = [
		{
			title: 'Photography',
			excerpt: 'Course about photography',
			category: 'category a',
		},
		{
			title: 'Music',
			excerpt: 'Course about music',
			category: 'category b',
		},
		{
			title: 'Audio',
			excerpt: 'Course about Audio',
			category: 'category c',
		},
	];

	beforeAll( async ( { browser } ) => {
		// Start to run request as admin
		await asAdmin( browser, async ( api ) => {
			for ( const course of courses ) {
				const category = await createCourseCategory( api, {
					name: course.category,
					description: '',
					slug: '',
				} );

				await createCourse( api, {
					...course,
					categoryIds: [ category.id ],
					lessons: [],
				} );
			}
		} );
	} );

	test( 'it should render a list of courses', async ( { page } ) => {
		const postTypePage = new PostType( page, 'page' );

		await postTypePage.goToPostTypeCreationPage();
		const courseList = await postTypePage.addQueryLoop( 'Course List' );
		await courseList.choosePattern( 'Courses displayed in a grid' );

		await postTypePage.publish();
		const published = await postTypePage.viewPage();

		for ( const course of courses ) {
			await expect(
				published.locator( `role=heading[name=${ course.title }]` )
			).toBeVisible();
			await expect(
				published.locator( `text='${ course.excerpt }'` )
			).toBeVisible();
			await expect(
				published.locator( `role=link[name='${ course.category }']` )
			).toBeVisible();
		}

		// It is possible to have more courses created by other test.
		const buttonsCount = await page
			.locator( `text='Start Course'` )
			.count();

		await expect(
			buttonsCount >= courses.length,
			'renders a start button by course'
		).toEqual( true );
	} );
} );
