/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );
/**
 * Internal dependencies
 */
const { createCourse, createCourseCategory } = require( '../../../helpers/api' );
const { getContextByRole } = require( '../../../helpers/context' );
const PostType = require( '../../../pages/admin/post-type' );

const { describe, use, beforeAll } = test;

describe( 'Courses List Block', () => {
	use( { storageState: getContextByRole( 'admin' ) } );

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

	beforeAll( async ( { request } ) => {
		for ( const course of courses ) {
			const category = await createCourseCategory( request, {
				name: course.category,
			} );

			await createCourse( request, {
				...course,
				categoryIds: [ category.id ],
			} );
		}
	} );

	test( 'it should render a list of courses', async ( { page } ) => {
		const postTypePage = new PostType( page, 'page' );

		await postTypePage.goToPostTypeCreationPage();
		const courseList = await postTypePage.addBlock( 'Course List' );
		await courseList.choosePattern( 'Courses displayed in a list' );

		await postTypePage.publish();
		await postTypePage.gotToPreviewPage();

		for ( const course of courses ) {
			await expect( page.locator( `text='${ course.title }'` ) ).toBeVisible();
			await expect( page.locator( `text='${ course.excerpt }'` ) ).toBeVisible();
			await expect( page.locator( `text='${ course.category }'` ) ).toBeVisible();
		}

		// It is possible to have more courses created by other test.
		const buttonsCount = await page.locator( `text='Start Course'` ).count();

		await expect( buttonsCount >= courses.length, 'renders a start button by course' ).toEqual( true );
	} );
} );
