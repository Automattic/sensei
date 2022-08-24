const { memoize } = require( 'lodash' );

const createStudent = async ( context, name ) => {
	const nonce = await getNonce( context );

	const response = await context.post( `/index.php?rest_route=/wp/v2/users`, {
		failOnStatusCode: true,
		headers: {
			'X-WP-Nonce': nonce,
		},
		data: {
			username: name,
			password: 'secret',
			email: `${ name }@example.com`,
			meta: { context: 'view' },
			slug: `som-slug#${ name }`,
		},
	} );
	return response.json();
};

const createCourse = async (
	context,
	{ title, excerpt = '', categoryIds }
) => {
	const nonce = await getNonce( context );

	const categories = categoryIds ? { 'course-category': categoryIds } : {};
	const response = await context.post(
		`/index.php?rest_route=/wp/v2/courses`,
		{
			failOnStatusCode: true,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: {
				status: 'publish',
				title,
				content:
					'<!-- wp:sensei-lms/button-take-course -->\n<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>\n<!-- /wp:sensei-lms/button-take-course -->\n\n<!-- wp:sensei-lms/button-contact-teacher -->\n<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">Contact Teacher</a></div>\n<!-- /wp:sensei-lms/button-contact-teacher -->\n\n<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->\n\n<!-- wp:sensei-lms/course-outline /-->',
				excerpt,
				...categories,
			},
		}
	);
	return response.json();
};

const createCourseCategory = async ( context, { name, description, slug } ) => {
	const nonce = await getNonce( context );
	const response = await context.post(
		`/index.php?rest_route=/wp/v2/course-category`,
		{
			failOnStatusCode: true,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: {
				name,
				description: description || `Some description for ${ name }`,
				slug,
			},
		}
	);
	return response.json();
};

const getNonce = memoize( async ( context ) => {
	const response = await context.get(
		'/wp-admin/admin-ajax.php?action=rest-nonce',
		{
			failOnStatusCode: true,
		}
	);
	return response.text();
} );

module.exports = {
	createStudent,
	createCourse,
	createCourseCategory,
};
