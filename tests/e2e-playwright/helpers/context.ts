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

type Params = {
	context: BrowserContext;
};
type Callback = ( param: Params ) => unknown;

/**
 * Execute the function as an admin.
 *
 * @param {Browser}  browser
 * @param {Function} fn      Callback.
 * @return {Promise<*>} Callback return value.
 */
export const asAdmin = async (
	{ browser }: { browser: Browser },
	fn: Callback
): Promise< unknown > => {
	const context = await browser.newContext( adminRole() );
	return fn( { context } );
};

export const studentRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'student' ),
} );
export const adminRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'admin' ),
} );

export const useAdminContext = async (
	browser: Browser
): Promise< APIRequestContext > => {
	const browserContext = await browser.newContext( adminRole() );
	return browserContext.request;
};


export const createBrowserContext = async ( browser: Browser, user: User ) => {
	const userPage = await browser.newPage();
	await login( userPage, { user: user.username, pwd: user.password } );

	await userPage.request.storageState( {
		path: getContextByRole( user.username ),
	} );
	return userPage.close();
};


export const createAdminBrowserContext = async ( page: Page ) => {
	await login( page, { user: 'admin', pwd: 'password' } );

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
};

async function login( page: Page, { user, pwd }: Credentials ) {
	await page.goto( 'http://localhost:8889/wp-login.php' );
	await page.locator( 'input[name="log"]' ).fill( user );
	await page.locator( 'input[name="pwd"]' ).fill( pwd );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();
}
