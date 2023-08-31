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
import { faker } from '@faker-js/faker';

const { describe, use, beforeAll } = test;

describe( 'Courses List Block', () => {
	use( editorRole() );

	const courses = [
		{
			title: faker.lorem.sentence( 2 ),
			excerpt: faker.lorem.sentence( 3 ),
			category: faker.lorem.slug( 2 ),
		},
		{
			title: faker.lorem.sentence( 2 ),
			excerpt: faker.lorem.sentence( 3 ),
			category: faker.lorem.slug( 2 ),
		},
		{
			title: faker.lorem.sentence( 2 ),
			excerpt: faker.lorem.sentence( 3 ),
			category: faker.lorem.slug( 2 ),
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

	test( 'it should render a list of courses', async ( { page } ) => {
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
