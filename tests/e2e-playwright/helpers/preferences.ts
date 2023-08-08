import { Browser } from '@playwright/test';
import { User } from './api';
import fs from 'fs/promises';
import { login, getContextByRole } from './context';

export type UserPreference = {
	path: string;
	key: string;
	initialState: {
		cookies: {
			name: string;
			value: string;
			domain: string;
			path: string;
			expires: number;
			httpOnly: boolean;
			secure: boolean;
			sameSite: 'Strict' | 'Lax' | 'None';
		}[];
		origins: {
			origin: string;
			localStorage: {
				name: string;
				value: string;
			}[];
		}[];
	};
};

export const createUserPreference = async (
	browser: Browser,
	user: User
): Promise< UserPreference > => {
	const userPage = await login( await browser.newPage(), user );
	const path = getContextByRole( user.username );
	const initialState = await userPage.request.storageState( { path } );

	return {
		path,
		key: `WP_PREFERENCES_USER_${ user.id }`,
		initialState,
	};
};

export const setDefaultPreferences = async (
	userPreference: UserPreference
): Promise< void > => {
	const skipValues = {
		'core/edit-post': { welcomeGuide: false },
		'core/edit-page': { welcomeGuide: false },
		'core/edit-site': { welcomeGuide: false },
	};

	const updated = {
		...userPreference.initialState,
		origins: [
			{
				origin: '/',
				localStorage: [
					{
						name: userPreference.key,
						value: JSON.stringify( skipValues ),
					},
				],
			},
		],
	};

	await fs.writeFile( userPreference.path, JSON.stringify( updated ) );
};
