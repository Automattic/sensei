/**
 * External dependencies
 */
import path from 'path';
import type { APIRequestContext, Browser, Page } from '@playwright/test';
import { User } from './api';
import { ADMIN, API, EDITOR, STUDENT, TEACHER } from '@e2e/factories/users';

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

export const adminApiRole = (): Record< string, string > => ( {
	storageState: getContextByRole( API.username ),
} );

export const studentRole = (): Record< string, string > => ( {
	storageState: getContextByRole( STUDENT.username ),
} );

export const teacherRole = (): Record< string, string > => ( {
	storageState: getContextByRole( TEACHER.username ),
} );

export const adminRole = (): Record< string, string > => ( {
	storageState: getContextByRole( ADMIN.username ),
} );

export const editorRole = (): Record< string, string > => ( {
	storageState: getContextByRole( EDITOR.username ),
} );

export const useAdminContext = async (
	browser: Browser
): Promise< APIRequestContext > => {
	const browserContext = await browser.newContext( adminRole() );
	return browserContext.request;
};

export const createAdminContext = async (
	page: Page
): Promise< APIRequestContext > => {
	const adminPage = await login( page, ADMIN );

	// it saves the request context
	await adminPage.request.storageState( {
		path: getContextByRole( 'admin' ),
	} );

	return page.request;
};

export const login = async ( page: Page, user: User ): Promise< Page > => {
	const response = await page.request.post(
		'http://localhost:8889/wp-login.php',
		{
			failOnStatusCode: true,
			form: {
				log: user.username,
				pwd: user.password,
			},
		}
	);

	await response.dispose();

	return page;
};
