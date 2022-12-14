/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
const PostType = require( '../post-type' );

class WizardModal {
	private readonly wizard: Locator;
	private readonly form: Locator;
	public readonly input: Locator;
	public readonly textArea: Locator;
	public readonly continueButton: Locator;
	public readonly continueWithFreeButton: Locator;
	public readonly startWithDefaultLayoutButton: Locator;

	constructor( locator: Locator ) {
		this.wizard = locator;
		this.form = this.wizard.locator( '.sensei-editor-wizard-step__form' );
		this.input = this.form.locator( 'input' ).first();
		this.textArea = this.form.locator( 'textarea' ).first();
		this.continueButton = this.wizard.locator( 'button:has-text("Continue")' );
		this.continueWithFreeButton = this.wizard.locator( 'button:has-text("Continue with Sensei Free")' );
		this.startWithDefaultLayoutButton = this.wizard.locator( 'button:has-text("Start with default layout")' );
	}
}

class ModuleBlock {
	private readonly module: Locator;
	public readonly title: Locator;
	public readonly addLessonField: Locator;

	constructor( locator: Locator ) {
		this.module = locator;
		this.title = this.module.locator( 'header textarea' );
		this.addLessonField = this.module.locator( '[aria-label="Block\\: Lesson"] textarea' ).last();
	}
}

class CourseOutline {
	private readonly outline: Locator;
	public readonly addModuleOrLessonButton: Locator;
	public readonly addModuleButton: Locator;
	public readonly moduleBlock: ModuleBlock;

	constructor( page: Page ) {
		this.outline = page.locator( '[aria-label="Block: Course Outline"]' ).first();
		this.addModuleOrLessonButton = this.outline.locator( '[aria-label="Add Module or Lesson"]' );
		this.addModuleButton = page.locator( 'button[role="menuitem"]:has-text("Module")' );
		this.moduleBlock = new ModuleBlock( this.outline.locator( '[aria-label="Block: Module"]' ).first() );
	}

	async click() {
		await this.outline.click();
	}
}

class CoursesPage extends PostType {
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
		this.createCourseButton = page.locator( 'a.page-title-action[href$="post-new.php?post_type=course"]:has-text("New Course")' );
		this.courseOutlineBlock = new CourseOutline( page );

		this.publishButton = page.locator( '[aria-label="Editor top bar"] >> text=Publish' );
		this.confirmPublishButton = page.locator( 'button:has-text("Submit for Review")' ).first();
		this.viewPreviewLink = page.locator( 'a:has-text("View Preview")' ).first();
	}

	async addModuleWithLesson( title: string, lessonTitle: string ) {
		const courseOutline = this.courseOutlineBlock;
		await courseOutline.click();
		await courseOutline.addModuleOrLessonButton.click();
		await courseOutline.addModuleButton.click();

		const moduleBlock = courseOutline.moduleBlock;
		await moduleBlock.title.fill( title );
		await moduleBlock.addLessonField.fill( lessonTitle );
	}
}

export default CoursesPage;
