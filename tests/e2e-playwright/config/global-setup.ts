/**
 * External dependencies
 */
import { retry } from '@lifeomic/attempt';
import { chromium } from '@playwright/test';

/**
 * Internal dependencies
 */
import { cleanAll as cleanDatabase, configureSite } from '../helpers/database';
import { createTeacher, createStudent } from '../helpers/api';
import { getContextByRole } from '../helpers/context';

export default async () => {
	cleanDatabase();
	configureSite();

	await retry( createUserContexts, {
		delay: 200,
		factor: 2,
		maxAttempts: 4,
	} );
};

const createUserContexts = async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();

	await createAdminBrowserContext( page );
	await createTeacherBrowserContext( page );
	await createStudentBrowserContext( page );

	await browser.close();
};

async function login( page, { user, pwd } ) {
	await page.goto( 'http://localhost:8889/wp-login.php' );
	await page.locator( 'input[name="log"]' ).fill( user );
	await page.locator( 'input[name="pwd"]' ).fill( pwd );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();
}

const createAdminBrowserContext = async ( page ) => {
	await login( page, { user: 'admin', pwd: 'password' } );

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
};

const createTeacherBrowserContext = async ( page ) => {
	await login( page, { user: 'admin', pwd: 'password' } );
	await createTeacher( page.request, 'teacher1' );
	await login( page, { user: 'teacher1', pwd: 'password' } );

	// Save the request context.
	await page.request.storageState( { path: getContextByRole( 'teacher' ) } );
};

const createStudentBrowserContext = async ( page ) => {
	await login( page, { user: 'admin', pwd: 'password' } );
	await createStudent( page.request, 'student1' );
	await login( page, { user: 'student1', pwd: 'password' } );

	// Saves the request context.
	await page.request.storageState( { path: getContextByRole( 'student' ) } );
};
