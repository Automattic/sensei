import { WpApiRequestContext } from './index';

export type UserResponse = {
	id: number;
	username: string;
	name: string;
	email: string;
};

export type User = {
	id?: string;
	username: string;
	password?: string;
	email?: string;
	roles?: string[];
	meta?: Record< string, unknown >;
	slug?: string;
};

export const createUser = async (
	api: WpApiRequestContext,
	user: User
): Promise< UserResponse > => {
	return api.post< UserResponse >( `/wp-json/wp/v2/users`, {
		email: `${ user.username }@example.com`,
		meta: { context: 'view' },
		...user,
	} );
};
