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

describe( 'Create Courses', () => {
	use( { storageState: getContextByRole( 'admin' ) } );

	test( 'it should create a course', async ( { page } ) => {
		const coursesPage = new PostType( page, 'course' );
		coursesPage.goToPostTypeListingPage();

		const createCourseButton = page.locator( 'a.page-title-action[href$="post-new.php?post_type=course"]:has-text("New Course")' );
		await expect( createCourseButton ).toBeVisible();
		await createCourseButton.click();

		// Close Welcome to the block editor dialog.
		await coursesPage.dialogCloseButton.click();

		// Fill in the course title and description.
		const wizardForm = page.locator( '.sensei-editor-wizard-step__form' ).first();
		await wizardForm.locator( 'input' ).first().fill( 'Test Create Course' );
		await wizardForm.locator( 'textarea' ).first().fill( 'Test Create Course Description' );

		// Click "Continue" button.
		await page.locator( '.sensei-editor-wizard__actions > button' ).click();
		// Click "Continue with Sensei Free" button.
		await page.locator( '.sensei-editor-wizard__actions > button' ).first().click();
		// Click "Start with default layout" button.
		await await page.locator( '.sensei-editor-wizard__actions > button' ).click();

		// Publish the course (publish method doesn't work as there is no redirect at this point).
		await page.locator( '[aria-label="Editor top bar"] >> text=Publish' ).click();
		await page.locator( '[aria-label="Editor publish"] >> text=Publish' ).first().click();

		await page.locator( 'a:has-text("View Course")' ).first().click();

		await expect( page.locator( 'h1:has-text("Test Create Course")' ) ).toBeVisible();
		await expect( page.locator( 'button:has-text("Take Course")' ) ).toBeVisible();
	} );
} );
