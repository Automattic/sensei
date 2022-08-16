/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );
/**
 * Internal dependencies
 */
const { createCourse } = require( '../../../helpers/api' );
const { describe, use, beforeAll } = test;

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const PostType = require( '../../../pages/admin/postType' );

describe( 'Courses List Block', () => {
	use( { storageState: getContextByRole( 'admin' ) } );
	const courses = [ 'course a', 'course b', 'course c' ];

	beforeAll( async ( { request } ) => {
		return Promise.all(
			courses.map( async ( course ) => createCourse( request, course ) )
		);
	} );

	test( 'it should render a list of courses', async ( { page } ) => {
		const postTypePage = new PostType( page, { postType: 'page' } );

		await postTypePage.goToNewPage();
		const courseList = await postTypePage.addBlock( 'Course List' );
		await courseList.choosePattern( 'Grid of courses' );

		await postTypePage.publish();
		await postTypePage.preview();

		await expect(
			page.locator( `text='${ courses[ 0 ] }'` )
		).toBeVisible();

		//TODO: Add more checks
	} );
} );
