/**
 * External dependencies
 */
import { retry } from '@lifeomic/attempt';
import { APIRequestContext, chromium } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	cleanAll as cleanDatabase,
	configureSite,
} from '@e2e/helpers/database';
import { createUser, User } from '@e2e/helpers/api';
import { createAdminContext } from '@e2e/helpers/context';
import {
	createUserPreference,
	setDefaultPreferences,
} from '@e2e/helpers/preferences';
import { GLOBAL_USERS } from '@e2e/factories/users';

export default async (): Promise< void > => {
	cleanDatabase();
	configureSite();

	return retry( setupDefaultUsers, {
		delay: 200,
		factor: 2,
		maxAttempts: 4,
	} );
};

const setupDefaultUsers = async (): Promise< void > => {
	// eslint-disable-next-line no-console
	console.log( 'Setting the users...' );

	const browser = await chromium.launch();
	const adminPage = await browser.newPage();

	const adminContext = await createAdminContext( adminPage );
	const createdUsers = await createGlobalUsers( adminContext, GLOBAL_USERS );

	await Promise.all(
		createdUsers.map( async ( user ) => {
			const userPreference = await createUserPreference( browser, user );
			return setDefaultPreferences( userPreference );
		} )
	);

	return browser.close();
};

const createGlobalUsers = async (
	adminContext: APIRequestContext,
	users: User[]
): Promise< User[] > => {
	return Promise.all(
		users.map( async ( user ) => {
			const created = await createUser( adminContext, user );
			return {
				...user,
				id: created.id,
			};
		} )
	);
};
