/**
 * External dependencies
 */
import { retry } from '@lifeomic/attempt';
import { Browser, chromium } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	cleanAll as cleanDatabase,
	configureSite,
} from '@e2e/helpers/database';
import { createUser, User, UserResponse } from '@e2e/helpers/api';
import {
	createAdminBrowserContext,
	createBrowserContext,
	useAdminContext,
} from '@e2e/helpers/context';
import { GLOBAL_USERS } from '@e2e/factories/users';

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
	await createGlobalUsers( browser, GLOBAL_USERS );

	await Promise.all(
		GLOBAL_USERS.map( ( user ) => createBrowserContext( browser, user ) )
	);

	await browser.close();
};

const createGlobalUsers = async (
	browser: Browser,
	users: User[]
): Promise< UserResponse[] > => {
	const adminContext = await useAdminContext( browser );

	return Promise.all(
		users.map( ( user ) => createUser( adminContext, user ) )
	);
};
