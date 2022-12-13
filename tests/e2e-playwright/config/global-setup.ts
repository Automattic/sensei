/**
 * External dependencies
 */
import { retry } from '@lifeomic/attempt';
import { Browser, chromium } from '@playwright/test';
import type { Page, APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	cleanAll as cleanDatabase,
	configureSite,
} from '@e2e/helpers/database';
import { createUser, User, UserResponse } from '@e2e/helpers/api';
import { getContextByRole } from '@e2e/helpers/context';

export default async (): Promise< void > => {
	cleanDatabase();
	configureSite();

	return retry( createUserContexts, {
		delay: 200,
		factor: 2,
		maxAttempts: 4,
	} );
};

const createUserContexts = async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();

	await createAdminBrowserContext( page );

	const users: User[] = [
		{
			username: 'teacher',
			email: 'teacher@teacher.com',
			password: 'password',
		},
		{
			username: 'student',
			email: 'student@student.com',
			password: 'password',
		},
	];
	await createUsers( browser, users );

	await Promise.all(
		users.map( ( user ) => createBrowserContext( browser, user ) )
	);

	await browser.close();
};

type Credentials = {
	user: string;
	pwd: string;
};
async function login( page: Page, { user, pwd }: Credentials ) {
	await page.goto( 'http://localhost:8889/wp-login.php' );
	await page.locator( 'input[name="log"]' ).fill( user );
	await page.locator( 'input[name="pwd"]' ).fill( pwd );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();
}

const createAdminBrowserContext = async ( page: Page ) => {
	await login( page, { user: 'admin', pwd: 'password' } );

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
};

const useAdminContext = async (
	browser: Browser
): Promise< APIRequestContext > => {
	const browserContext = await browser.newContext( {
		storageState: getContextByRole( 'admin' ),
	} );
	return browserContext.request;
};

const createUsers = async (
	browser: Browser,
	users: User[]
): Promise< UserResponse[] > => {
	const adminContext = await useAdminContext( browser );

	return Promise.all(
		users.map( ( user ) => createUser( adminContext, user ) )
	);
};

const createBrowserContext = async ( browser: Browser, user: User ) => {
	const userPage = await browser.newPage();
	await login( userPage, { user: user.username, pwd: user.password } );

	await userPage.request.storageState( {
		path: getContextByRole( user.username ),
	} );
	return userPage.close();
};
