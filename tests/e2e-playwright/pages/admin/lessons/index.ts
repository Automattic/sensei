/**
 * External dependencies
 */
import type { Locator, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import PostType from '@e2e/pages/admin/post-type';

export default class LessonList extends PostType {
	private readonly newLesson: Locator;

	constructor( page: Page ) {
		super( page, 'lesson' );
		this.newLesson = page.locator( 'text=New Lesson' );
	}

	async clickNewLesson(): Promise< LessonEdit > {
		this.newLesson.click();

		return new LessonEdit( this.page );
	}
}

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

export class LessonEdit extends PostType {
	public readonly wizardModal: WizardModal;

	constructor( page: Page ) {
		super( page, 'lesson' );

		const wizardLocator = page.locator( '.sensei-editor-wizard' );
		this.wizardModal = new WizardModal( wizardLocator );
	}

	async addLessonContent( content: string ): Promise< LessonEdit > {
		await this.page
			.locator(
				'[aria-label="Empty block; start writing or type forward slash to choose a block"]'
			)
			.fill( content );

		return this;
	}

	async clickSaveDraft(): Promise< LessonEdit > {
		await this.page.locator( 'text=Save draft' ).click();

		return this;
	}

	async clickPublish(): Promise< LessonEdit > {
		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();
		await this.page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first()
			.click();

		return this;
	}

	async clickViewLesson(): Promise< Page > {
		await this.page
			.locator( '[aria-label="Editor publish"] >> text=View Lesson' )
			.click();

		return this.page;
	}
}
