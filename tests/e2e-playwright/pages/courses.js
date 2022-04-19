/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

const PATH = 'wp-admin/edit.php?post_type=course';

class CoursesPage {
	constructor( page ) {
		this.page = page;
	}

	async open() {
		await this.page.goto( PATH );
	}

	async createCourse( courseName = 'some course' ) {
		await this.page.locator( 'text=New Course' ).click();
		await this.page.locator( '[aria-label="Close dialog"]' ).click();
		await this.page.locator( '[aria-label="Close dialog"]' ).click();
		await this.page
			.locator( '[aria-label="Course name"]' )
			.fill( courseName );

		await this.page.locator( 'text=Create a lesson' ).click();
		await this.page
			.locator( '[aria-label="Block\\: Lesson"] textarea' )
			.fill( 'Some lesson' );

		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();

		await this.page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first()
			.click();

		return expect(
			this.page.locator( `text=${ courseName } is now live.` )
		).toBeTruthy();
	}
}

module.exports = CoursesPage;
