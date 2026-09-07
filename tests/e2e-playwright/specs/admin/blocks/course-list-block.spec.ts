/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { asAdmin, createCourse, createCourseCategory } from '@e2e/helpers/api';
import PostType from '@e2e/pages/admin/post-type';
import { editorRole } from '@e2e/helpers/context';

const { describe, use, beforeAll } = test;

describe( 'Courses List Block', () => {
	use( editorRole() );

	const courses = [
		{
			title: 'Intro to Astronomy',
			excerpt: 'Learn the basics of stargazing.',
			category: 'Science',
		},
		{
			title: 'Creative Writing Workshop',
			excerpt: 'Craft compelling short stories.',
			category: 'Arts',
		},
		{
			title: 'Home Gardening Essentials',
			excerpt: 'Grow vegetables year round.',
			category: 'Lifestyle',
		},
	];

	beforeAll( async () => {
		// Start to run request as admin
		await asAdmin( async ( api ) => {
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

	/**
	 * This test is failing because the course list block patterns are now not working on WordPress 6.9. A fix was merged but not released yet.
	 *
	 * @see https://github.com/Automattic/sensei/issues/7873
	 */
	test.fixme( 'it should render a list of courses', async ( { page } ) => {
		const postTypePage = new PostType( page, 'page' );

		await postTypePage.goToPostTypeCreationPage();
		const courseList = await postTypePage.addQueryLoop( 'Course List' );
		await courseList.choosePattern( 'Courses displayed in a grid' );

		await postTypePage.publish();
		const published = await postTypePage.viewPage();

		for ( const course of courses ) {
			await expect(
				published.getByRole( 'heading', { name: course.title } )
			).toBeVisible();

			await expect(
				published.getByRole( 'link', { name: course.category } )
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
