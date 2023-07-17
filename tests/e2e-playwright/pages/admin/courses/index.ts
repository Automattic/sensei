/**
 * External dependencies
 */
import type { Locator, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import PostType from '@e2e/pages/admin/post-type';

class WizardModal {
	private readonly base: Locator;

	constructor( locator: Locator ) {
		this.base = locator;
	}

	async setCourse( { title, description } ) {
		await this.base.getByLabel( 'course titled' ).fill( title );
		await this.base.getByLabel( 'course description' ).fill( description );
	}

	async finishWithDefaultLayout() {
		await this.base.getByRole( 'button', { name: 'Continue' } ).click();

		await this.base
			.getByRole( 'button', {
				name: 'Continue with Sensei Free',
			} )
			.click();

		return this.base
			.getByRole( 'button', {
				name: 'Start with default layout',
			} )
			.click();
	}
}

class ModuleBlock {
	private readonly module: Locator;
	public readonly title: Locator;
	public readonly addLessonField: Locator;

	constructor( locator: Locator ) {
		this.module = locator;
		this.title = this.module.locator( 'header textarea' );
		this.addLessonField = this.module
			.locator( '[aria-label="Block\\: Lesson"] textarea' )
			.last();
	}
}

class CourseOutline {
	private readonly outline: Locator;
	public readonly addModuleOrLessonButton: Locator;
	public readonly addModuleButton: Locator;
	public readonly moduleBlock: ModuleBlock;

	constructor( page: Page ) {
		this.outline = page
			.locator( '[aria-label="Block: Course Outline"]' )
			.first();
		this.addModuleOrLessonButton = this.outline.locator(
			'[aria-label="Add Module or Lesson"]'
		);
		this.addModuleButton = page.locator(
			'button[role="menuitem"]:has-text("Module")'
		);
		this.moduleBlock = new ModuleBlock(
			this.outline.locator( '[aria-label="Block: Module"]' ).first()
		);
	}

	async click() {
		await this.outline.click();
	}
}

export default class CoursesPage extends PostType {
	public readonly wizardModal: WizardModal;
	public readonly createCourseButton: Locator;
	public readonly publishButton: Locator;
	public readonly confirmPublishButton: Locator;
	public readonly viewPreviewLink: Locator;
	public readonly courseOutlineBlock: CourseOutline;

	constructor( page: Page ) {
		super( page, 'course' );

		const wizardLocator = page.locator( '.sensei-editor-wizard' );
		this.wizardModal = new WizardModal( wizardLocator );

		this.createCourseButton = page.locator(
			'a.page-title-action[href$="post-new.php?post_type=course"]:has-text("New Course")'
		);

		this.courseOutlineBlock = new CourseOutline( page );

		this.publishButton = page.locator(
			'[aria-label="Editor top bar"] >> text=Publish'
		);

		this.confirmPublishButton = page
			.locator( 'button:has-text("Submit for Review")' )
			.first();

		this.viewPreviewLink = page
			.locator( 'a:has-text("View Preview")' )
			.first();
	}

	async addModuleWithLesson(
		title: string,
		lessonTitle: string
	): Promise< ModuleBlock > {
		const courseOutline = this.courseOutlineBlock;
		await courseOutline.click();
		await courseOutline.addModuleOrLessonButton.click();
		await courseOutline.addModuleButton.click();

		const moduleBlock = courseOutline.moduleBlock;
		await moduleBlock.title.fill( title );
		await moduleBlock.addLessonField.fill( lessonTitle );

		return moduleBlock;
	}
}
