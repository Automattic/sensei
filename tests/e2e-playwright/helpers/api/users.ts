import { APIRequestContext } from '@playwright/test';
import { createApiContext } from './index';

export type UserResponse = {
	id: number;
	username: string;
	name: string;
	email: string;
};

export type User = {
	id?: number;
	username: string;
	password?: string;
	email?: string;
	roles?: string[];
	meta?: Record< string, unknown >;
	slug?: string;
};

export const createUser = async (
	context: APIRequestContext,
	user: User
): Promise< UserResponse > => {
	const api = await createApiContext( context );

	return api.post( `/wp-json/wp/v2/users`, {
		email: `${ user.username }@example.com`,
		meta: { context: 'view' },
		...user,
	} );
};
