/* eslint-disable no-console */
/**
 * External dependencies
 */
import { chromium } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	cleanAll as cleanDatabase,
	cliAsync,
	configureSite,
} from '@e2e/helpers/database';
import { User } from '@e2e/helpers/api';
import {
	createUserPreference,
	setDefaultPreferences,
} from '@e2e/helpers/preferences';
import { GLOBAL_USERS } from '@e2e/factories/users';
import { createAdminContext } from '@e2e/helpers/context';

export default async (): Promise< void > => {
	cleanDatabase();
	configureSite();
	await cleanAllPlugins();
	await setupDefaultUsers();
};

const cleanAllPlugins = async () => {
	await cliAsync( 'wp plugin deactivate --all --exclude=sensei,sensei-lms' );

	if ( needActivateGutenberg() ) {
		console.log( 'Installing and activating Gutenberg plugin...' );
		await cliAsync( 'wp plugin install gutenberg' );
		await cliAsync( 'wp plugin activate gutenberg' );
	}
};

const needActivateGutenberg = () => {
	return (
		process.env.ENABLE_GUTENBERG &&
		JSON.parse( process.env.ENABLE_GUTENBERG )
	);
};

const setupDefaultUsers = async (): Promise< void > => {
	// eslint-disable-next-line no-console
	console.log( 'Setting the users...' );

	const browser = await chromium.launch();
	const adminPage = await browser.newPage();

	await createAdminContext( adminPage );
	const createdUsers = await createGlobalUsers( GLOBAL_USERS );

	await Promise.all(
		createdUsers.map( async ( user ) => {
			const userPreference = await createUserPreference( browser, user );
			return setDefaultPreferences( userPreference );
		} )
	);

	return browser.close();
};

const createGlobalUsers = async ( users: User[] ): Promise< User[] > => {
	return Promise.all( users.map( ( user ) => setupUser( user ) ) );
};

async function setupUser( user: User ) {
	const command = [
		'wp user create',
		user.username,
		user.email,
		user.roles?.length ? `--role=${ user.roles.join( ',' ) }` : '',
		`--user_pass=${ user.password }`,
		'--porcelain',
	].join( ' ' );

	await cliAsync( command );

	const response = await cliAsync(
		`wp user get ${ user.username } --format=json`
	);

	const userDetails = JSON.parse(
		response
			.toString()
			.match( /\{(.*?)\}/ )
			.at( 0 )
	);
	return {
		id: userDetails.ID,
		...user,
	};
}
