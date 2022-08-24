/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );
/**
 * Internal dependencies
 */
const {
	createCourse,
	createCourseCategory,
} = require( '../../../helpers/api' );
const { describe, use, beforeAll } = test;

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const PostType = require( '../../../pages/admin/postType' );

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
		courses.forEach( async ( course ) => {
			const category = await createCourseCategory( request, {
				name: course.category,
			} );

			await createCourse( request, {
				...course,
				categoryIds: [ category.id ],
			} );
		} );
	} );

	test( 'it should render a list of courses', async ( { page } ) => {
		const postTypePage = new PostType( page, { postType: 'page' } );

		await postTypePage.goToNewPage();
		const courseList = await postTypePage.addBlock( 'Course List' );
		await courseList.choosePattern( 'Grid of courses with details' );

		await postTypePage.publish();
		await postTypePage.preview();

		courses.forEach( async ( course ) => {
			await expect(
				page.locator( `text='${ course.title }'` ),
				'renders the title'
			).toBeVisible();

			await expect(
				page.locator( `text='${ course.excerpt }'` ),
				'renders the excerpts'
			).toBeVisible();

			await expect(
				page.locator( `text='${ course.category }'` ),
				'renders the categories'
			).toBeVisible();
		} );

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
