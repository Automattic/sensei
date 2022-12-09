const PATH = '/wp-admin/';

class DashboardPage {
	private readonly page: Page;
	public readonly mainMenu: Locator;
	public readonly senseiMenuItem: Locator;
	public readonly coursesMenuItem: Locator;

	constructor( page: Page ) {
		this.page = page;
		this.mainMenu = page.locator( '#adminmenu' );
		this.senseiMenuItem = this.mainMenu.locator( 'a[href$="admin.php?page=sensei"]:has-text("Sensei LMS")' );
		this.coursesMenuItem = this.mainMenu.locator( 'a[href$="edit.php?post_type=course"]:has-text("Courses")' );
	}

	async goTo() {
		await this.page.goto( PATH );
	}

	async getSenseiMenuItem() {
		return this.senseiMenuItem;
	}

	async getCoursesMenuItem() {
		return this.coursesMenuItem;
	}
}

module.exports = DashboardPage;
