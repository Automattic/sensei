/**
 * External dependencies
 */
import { APIRequestContext, BrowserContext } from '@playwright/test';
/**
 * Internal dependencies
 */
import { lessonSimple } from './lesson-templates';

/**
 * Wrap the context, adding a post helper that sends a nonce.
 *
 * @param {APIRequestContext} context
 * @return {Promise<*&{post: (function(*, *): Promise<*>)}>}
 */
const createApiContext = async ( context ) => {
	const baseUrl = 'http://localhost:8889';
	const nonce = await getNonce( context );
	return {
		...context,
		post: async ( url, data ) =>
			(
				await context.post( baseUrl + url, {
					failOnStatusCode: true,
					headers: {
						'X-WP-Nonce': nonce,
					},
					data,
				} )
			 )?.json(),
	};
};

/**
 *
 * @param {APIRequestContext} context
 * @param {string} name
 */
export const createStudent = async ( context, name ) => {
	const api = await createApiContext( context );

	return api.post( `/wp-json/wp/v2/users`, {
		username: name,
		password: 'password',
		email: `${ name }@example.com`,
		meta: { context: 'view' },
		slug: name,
	} );
};

export const createTeacher = async ( context, name ) => {
	const api = await createApiContext( context );

	return api.post( `/wp-json/wp/v2/users`, {
		username: name,
		password: 'password',
		email: `${ name }@example.com`,
		roles: [ 'teacher' ],
		meta: { context: 'view' },
		slug: name,
	} );
};

export const createCourse = async ( context, courseDefinition ) => {
	const { title, slug, excerpt = '', categoryIds, lessons } = courseDefinition;
	const api = await createApiContext( context );

	const categories = categoryIds ? { 'course-category': categoryIds } : {};
	const course = await api.post( `/wp-json/wp/v2/courses`, {
		status: 'publish',
		slug,
		title,
		content:
			'<!-- wp:sensei-lms/button-take-course -->\n<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>\n<!-- /wp:sensei-lms/button-take-course -->\n\n<!-- wp:sensei-lms/button-contact-teacher -->\n<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">Contact Teacher</a></div>\n<!-- /wp:sensei-lms/button-contact-teacher -->\n\n<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->\n\n<!-- wp:sensei-lms/course-outline /-->',
		excerpt,
		...categories,
	} );

	if ( lessons ) {
		course.lessons = [];

		const structure = lessons.map( ( lesson ) => ( {
			...lesson,
			type: 'lesson',
			draft: false,
		} ) );

		const newLessons = await api.post( `/wp-json/sensei-internal/v1/course-structure/${ course.id }`, {
			structure,
		} );

		for ( const lesson of newLessons.flatMap( ( item ) => item.lessons ?? item ) ) {
			const lessonData = lessons.find( ( { title } ) => title === lesson.title );
			const lessonResult = await addLessonContent( api, {
				...lessonData,
				...lesson,
			} );
			course.lessons.push( lessonResult );
		}
	}

	return course;
};

const addLessonContent = async ( api, { id, content = lessonSimple(), ...lesson } ) => {
	return api.post( `/wp-json/wp/v2/lessons/${ id }`, {
		...lesson,
		status: 'publish',
		id,
		content,
	} );
};

export const createCourseCategory = async ( context, category ) => {
	const { name, description, slug } = category;
	const api = await createApiContext( context );
	return api.post( `/wp-json/wp/v2/course-category`, {
		name,
		description: description || `Some description for ${ name }`,
		slug,
	} );
};

const getNonce = async ( context ) => {
	const response = await context.get( 'http://localhost:8889/wp-admin/admin-ajax.php?action=rest-nonce', {
		failOnStatusCode: true,
	} );
	return response.text();
};

export const disableGlobalLearningMode = async ( context: BrowserContext ) => {
	const page = await context.newPage();
	await page.goto( '/wp-admin/admin.php?page=sensei-settings' );
	// Click text=Appearance new
	await page.locator( 'text=Appearance new' ).click();
	// Click text=Enable for all courses
	await page.locator( 'text=Enable for all courses' ).click();
	// Click text=Save Changes
	await page.locator( 'text=Save Changes' ).click();
	await page.close();
};
