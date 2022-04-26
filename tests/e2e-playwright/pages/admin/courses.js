/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

const PATH = '/wp-admin/post-new.php?post_type=course';

class CoursesPage {
	constructor( page ) {
		this.page = page;
	}

	async goTo() {
		return this.page.goto( PATH );
	}

	async closeTutorial() {
		// Tutorial requires 2 clicks
		await this.page.locator( '[aria-label="Close dialog"]' ).click();
		return this.page.locator( '[aria-label="Close dialog"]' ).click();
	}

	async createCourse( courseName = 'some course' ) {
		await this.closeTutorial();
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
