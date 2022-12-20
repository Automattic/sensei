/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import PostType from '@e2e/pages/admin/post-type';
import { WizardModal } from './wizard';

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

	async setLessonCourse( courseTitle: string ): Promise< LessonEdit > {
		await this.page
			.getByRole( 'region', { name: 'Editor settings' } )
			.getByRole( 'button', { name: 'Lesson' } )
			.click();
		await this.page.getByRole( 'textbox', { name: 'None' } ).click();
		await this.page.getByRole( 'option', { name: courseTitle } ).click();

		return this;
	}

	async clickSaveDraft(): Promise< LessonEdit > {
		await this.page.locator( 'text=Save draft' ).click();

		return this;
	}

	async publish(): Promise< void > {
		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();

		await this.page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first()
			.click();
	}

	async clickViewLesson(): Promise< Page > {
		await this.page
			.locator( '[aria-label="Editor publish"] >> text=View Lesson' )
			.click();

		return this.page;
	}
}
