/**
 * External dependencies
 */
import path from 'path';
import type {
	APIRequestContext,
	Browser,
	BrowserContext,
	Page,
} from '@playwright/test';
import { User } from './api';
import { ADMIN } from '@e2e/fixtures/users';
const CONTEXT_DIR = path.resolve( __dirname, '../contexts' );

/**
 * @typedef {"admin"} UserRole
 */
/**
 * Returns the browser context by user role. E.g "admin", "student"
 *
 * @param {string} userRole
 */
export const getContextByRole = ( userRole: string ): string =>
	path.resolve( CONTEXT_DIR, `${ userRole }.json` );

export const studentRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'student' ),
});

export const adminRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'admin' ),
} );

export const useAdminContext = async (
	browser: Browser
): Promise< APIRequestContext > => {
	const browserContext = await browser.newContext( adminRole() );
	return browserContext.request;
};

export const createBrowserContext = async (
	browser: Browser,
	user: User
): Promise< void > => {
	const userPage = await browser.newPage();
	await login( userPage, user );

	await userPage.request.storageState( {
		path: getContextByRole( user.username ),
	} );
	return userPage.close();
};

export const createAdminBrowserContext = async (
	page: Page
): Promise< void > => {
	await login( page, ADMIN );

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
};

async function login( page: Page, user: User ) {
	await page.goto( 'http://localhost:8889/wp-login.php' );
	await page.locator( 'input[name="log"]' ).fill( user.username );
	await page.locator( 'input[name="pwd"]' ).fill( user.password );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();
}
