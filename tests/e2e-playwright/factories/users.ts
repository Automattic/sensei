import { User } from '@e2e/helpers/api';

export const ADMIN: User = {
	username: 'admin',
	password: 'password',
};

export const GLOBAL_USERS: User[] = [
	{
		username: 'teacher',
		email: 'teacher@teacher.com',
		password: 'password',
		roles: [ 'teacher' ],
	},
	{
		username: 'student',
		email: 'student@student.com',
		password: 'password',
	},
	{
		username: 'editor',
		email: 'editor@student.com',
		password: 'password',
		roles: [ 'editor' ],
	},
];
