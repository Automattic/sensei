import { User } from '@e2e/helpers/api';

export const SYSTEM_ADMIN: User = {
	username: 'admin',
	password: 'password',
};

export const API: User = {
	username: 'api',
	email: 'api@api.com',
	password: 'password',
	roles: [ 'administrator' ],
};

export const ADMIN: User = {
	username: 'lms-administrator',
	email: 'lms@lms.com',
	password: 'password',
	roles: [ 'administrator' ],
};

export const TEACHER: User = {
	username: 'teacher',
	email: 'teacher@teacher.com',
	password: 'password',
	roles: [ 'teacher' ],
};

export const STUDENT: User = {
	username: 'student',
	email: 'student@student.com',
	password: 'password',
};

export const EDITOR: User = {
	username: 'editor',
	email: 'editor@student.com',
	password: 'password',
	roles: [ 'editor' ],
};

export const GLOBAL_USERS: User[] = [ ADMIN, TEACHER, STUDENT, EDITOR, API ];
