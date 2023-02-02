/**
 * External dependencies
 */
import type { Locator, Page } from '@playwright/test';
import PostType from '@e2e/pages/admin/post-type';

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
		this.continueButton = this.wizard.locator(
			'button:has-text("Continue")'
		);
		this.continueWithFreeButton = this.wizard.locator(
			'button:has-text("Continue with Sensei Free")'
		);
		this.startWithDefaultLayoutButton = this.wizard.locator(
			'button:has-text("Start with default layout")'
		);
	}
}

export default class CoursesPage extends PostType {
	public readonly wizardModal: WizardModal;
	public readonly createCourseButton: Locator;
	public readonly publishButton: Locator;
	public readonly confirmPublishButton: Locator;
	public readonly viewPreviewLink: Locator;

	constructor( page: Page ) {
		super( page, 'course' );

		const wizardLocator = page.locator( '.sensei-editor-wizard' );
		this.wizardModal = new WizardModal( wizardLocator );

		this.createCourseButton = page.locator(
			'a.page-title-action[href$="post-new.php?post_type=course"]:has-text("New Course")'
		);

		this.publishButton = page.locator(
			'[aria-label="Editor top bar"] >> text=Publish'
		);
		this.confirmPublishButton = page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first();

		this.viewPreviewLink = page
			.locator( 'a:has-text("View Preview")' )
			.first();
	}
}
