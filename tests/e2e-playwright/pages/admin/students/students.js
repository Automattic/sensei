const PATH = '/wp-admin/edit.php?post_type=course&page=sensei_learners';

class StudentsModalFragment {
	constructor( locator ) {
		this.base = locator;
		this.addToCourseButton = this.base.locator(
			'button:has-text("Add to Course")'
		);

		this.removeFromCourseButton = this.base.locator(
			'button:has-text("Remove from Course")'
		);
	}
	async selectCourse( courseName ) {
		this.base.locator( `label:has-text("${ courseName }")` ).check();
	}
}

class StudentsPage {
	constructor( page ) {
		this.page = page;
		this.actions = {
			addToCourse: page.locator( `text=Add to Course` ),
		};
		this.modal = new StudentsModalFragment(
			page.locator( '[role=dialog]' )
		);
		this.enrolledCoursesColumn = page.locator(
			'[data-colname="Enrolled Courses"]'
		);
	}

	// Migrate to role selector as soon it is available.
	async getRowByStudent( studentName ) {
		return this.page.locator(
			`tr:has([data-user-name="${ studentName }"])`
		);
	}
	async goTo() {
		return this.page.goto( PATH );
	}

	async openStudentAction( studentName, action ) {
		await this.page
			.locator( `[data-user-name="${ studentName }"]` )
			.locator( `button[aria-label="Select an action"]` )
			.click();

		return this.page.locator( `button:has-text("${ action }")` ).click();
	}
}

module.exports = StudentsPage;
