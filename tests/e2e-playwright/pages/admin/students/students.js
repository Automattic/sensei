const PATH = '/wp-admin/edit.php?post_type=course&page=sensei_learners';

class StudentsModalFragment {
	constructor( locator ) {
		this.base = locator;
		this.addToCourseButton = this.base.locator( 'text=Add to Course' );
	}
	async selectCourse( courseName ) {
		this.base.locator( `label:has-text("${ courseName }")` ).check();
	}
}

class StudentsPage {
	constructor( page ) {
		this.page = page;
		this.title = page.locator( 'role=heading[level=1]' );
		this.actions = {
			addToCourse: page.locator( `text=Add to Course` ),
		};
		this.modal = new StudentsModalFragment( page.locator( 'role=dialog' ) );
		this.enrolledCoursesColumn = page.locator(
			'[data-colname="Enrolled Courses"]'
		);
	}

	async goTo() {
		return this.page.goto( PATH );
	}

	async openStudentAction( studentName, action ) {
		await this.page
			.locator( `[data-user-name="${ studentName }"]` )
			.locator( `role=button[name="Select an action"]` )
			.click();

		return this.page.locator( `role=menuitem[name="${ action }"]` ).click();
	}
}

module.exports = StudentsPage;
