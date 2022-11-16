/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );
/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const PostType = require( '../../../pages/admin/post-type' );

const { describe, use, beforeAll } = test;

describe( 'Courses List', () => {
	use( { storageState: getContextByRole( 'admin' ) } );

	test( 'it has a Courses menu item in the page', async ( { page } ) => {
		await page.goto( '/wp-admin/' );

		const menu = page.locator( '#adminmenu' );
		await expect( menu ).toBeVisible();

		const senseiMenuItem = menu.locator( 'a[href$="admin.php?page=sensei"]:has-text("Sensei LMS")' );

		await expect( senseiMenuItem ).toBeVisible();

		await senseiMenuItem.click();
		const menuItem = page.locator( '#adminmenu a:has-text("Courses")' );
		await expect( menuItem ).toHaveAttribute( 'href', 'edit.php?post_type=course' );

		await menuItem.click();
		await expect( page.locator( '.sensei-custom-navigation__title h1' ) ).toHaveText( 'Courses' );
	} );
} );
