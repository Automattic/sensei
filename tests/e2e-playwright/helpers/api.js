const createStudent = async ( context, name ) => {
	const nonce = await getNonce( context );

	const response = await context.post(
		`http://localhost:8889/index.php?rest_route=/wp/v2/users`,
		{
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
		}
	);
	return response.json();
};

const createCourse = async ( context, name ) => {
	const nonce = await getNonce( context );
	const response = await context.post(
		`http://localhost:8889/index.php?rest_route=/wp/v2/courses`,
		{
			failOnStatusCode: true,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: {
				status: 'publish',
				title: name,
				content:
					'<!-- wp:sensei-lms/button-take-course -->\n<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>\n<!-- /wp:sensei-lms/button-take-course -->\n\n<!-- wp:sensei-lms/button-contact-teacher -->\n<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">Contact Teacher</a></div>\n<!-- /wp:sensei-lms/button-contact-teacher -->\n\n<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->\n\n<!-- wp:sensei-lms/course-outline /-->',
				excerpt: '',
			},
		}
	);
	return response.json();
};

const getNonce = async ( context ) => {
	const response = await context.get(
		'http://localhost:8889/wp-admin/admin-ajax.php?action=rest-nonce',
		{
			failOnStatusCode: true,
		}
	);
	return response.text();
};

module.exports = {
	createStudent,
	createCourse,
};
