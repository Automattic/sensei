import type { Locator, Page, Response } from '@playwright/test';

const PATH = '/wp-admin/admin.php?page=sensei_learners';

class StudentsModalFragment {
	addToCourseButton: Locator;
	removeFromCourseButton: Locator;

	constructor( private base: Locator ) {
		this.addToCourseButton = this.base.locator(
			'button:has-text("Add to Course")'
		);

		this.removeFromCourseButton = this.base.locator(
			'button:has-text("Remove from Course")'
		);
	}
	async selectCourse( courseName: string ) {
		this.base.locator( `label:has-text("${ courseName }")` ).check();
	}
}

export default class StudentsPage {
	modal: StudentsModalFragment;
	enrolledCoursesColumn: Locator;

	constructor( private page: Page ) {
		this.modal = new StudentsModalFragment(
			page.locator( '[role=dialog]' )
		);
		this.enrolledCoursesColumn = page.locator(
			'[data-colname="Enrolled Courses"]'
		);
	}

	// Migrate to role selector as soon it is available.
	async getRowByStudent( studentName: string ): Promise< Locator > {
		return this.page.locator(
			`tr:has([data-user-name="${ studentName }"])`
		);
	}
	async goTo(): Promise< Response > {
		return this.page.goto( PATH );
	}

	async openStudentAction(
		studentName: string,
		action: string
	): Promise< void > {
		await this.page
			.locator( `[data-user-name="${ studentName }"]` )
			.locator( `button[aria-label="Select an action"]` )
			.click();

		return this.page.locator( `button:has-text("${ action }")` ).click();
	}
}
