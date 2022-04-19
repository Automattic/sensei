/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

const PATH = '/wp-admin/edit.php?post_type=course&page=sensei_learners';

class StudentsPage {
	constructor( page ) {
		this.page = page;
		this.title = page.locator( 'role=heading[level=1]' );
		this.actionMenu = page.locator( '[aria-label="Select an action"]' );
		this.actions = {
			addToCourse: page.locator( `text=Add to Course` ),
		};

		this.modal = page.locator( 'role=dialog' );
	}

	async open() {
		await this.page.goto( PATH );
		return expect( this.title ).toHaveText( 'Students' );
	}

	async openAddToCourseModal() {
		await this.actionMenu.click();
		await this.actions.addToCourse.click();
		return expect( this.modal ).toContainText( 'Choose Course' );
	}
}

module.exports = StudentsPage;
